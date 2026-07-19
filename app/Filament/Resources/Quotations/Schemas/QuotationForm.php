<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\Service;
use App\Support\QuotationCalculator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class QuotationForm
{
    protected static function calculateLineTotal(
        Get $get,
        Set $set
    ): void {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $discount = (float) ($get('discount') ?? 0);

        $lineTotal = ($quantity * $unitPrice) - $discount;

        $set('line_total', max(0, $lineTotal));

        self::calculateFinancialSummary($get, $set);
    }

    protected static function calculateFinancialSummary(
        Get $get,
        Set $set,
        bool $fromRepeater = true,
    ): void {
        if ($fromRepeater) {
            $items = $get('../../items') ?? [];

            $subtotalPath = '../../subtotal';
            $taxPath = '../../tax';
            $grandTotalPath = '../../grand_total';

            $discountType =
                $get('../../discount_type') ?? 'fixed';

            $discountValue =
                (float) ($get('../../discount_value') ?? 0);

            $taxApplicable =
                (bool) ($get('../../tax_applicable') ?? false);

            $taxPercentage =
                (float) ($get('../../tax_percentage') ?? 18);
        } else {
            $items = $get('items') ?? [];

            $subtotalPath = 'subtotal';
            $taxPath = 'tax';
            $grandTotalPath = 'grand_total';

            $discountType =
                $get('discount_type') ?? 'fixed';

            $discountValue =
                (float) ($get('discount_value') ?? 0);

            $taxApplicable =
                (bool) ($get('tax_applicable') ?? false);

            $taxPercentage =
                (float) ($get('tax_percentage') ?? 18);
        }

        $result = QuotationCalculator::calculate(
            items: $items,
            discountType: $discountType,
            discountValue: $discountValue,
            taxApplicable: $taxApplicable,
            taxPercentage: $taxPercentage,
        );

        $set($subtotalPath, $result['subtotal']);
        $set($taxPath, $result['tax']);
        $set($grandTotalPath, $result['grand_total']);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Quotation Information
                |--------------------------------------------------------------------------
                */

                Section::make('Quotation Information')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                TextInput::make('quotation_code')
                                    ->label('Quotation Code')
                                    ->default(
                                        fn (): string =>
                                            Quotation::nextQuotationCode()
                                    )
                                    ->readOnly()
                                    ->dehydrated(false),

                                Select::make('lead_id')
                                    ->label('Lead')
                                    ->relationship(
                                        'lead',
                                        'company_name'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(function (): ?int {
                                        $meetingId =
                                            request()->integer('meeting');

                                        return $meetingId
                                            ? Meeting::find($meetingId)
                                                ?->lead_id
                                            : null;
                                    })
                                    ->disabled(
                                        fn ($record): bool =>
                                            filled($record)
                                            || request()->has('meeting')
                                    )
                                    ->dehydrated(),

                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship(
                                        'customer',
                                        'display_name'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(function (): ?int {
                                        $meetingId =
                                            request()->integer('meeting');

                                        return $meetingId
                                            ? Meeting::find($meetingId)
                                                ?->customer_id
                                            : null;
                                    })
                                    ->disabled(
                                        fn ($record): bool =>
                                            filled($record)
                                            || request()->has('meeting')
                                    )
                                    ->dehydrated(),

                                Select::make('meeting_id')
                                    ->label('Meeting')
                                    ->relationship(
                                        'meeting',
                                        'meeting_title'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(
                                        fn (): ?int =>
                                            request()->integer('meeting')
                                            ?: null
                                    )
                                    ->disabled(
                                        fn ($record): bool =>
                                            filled($record)
                                            || request()->has('meeting')
                                    )
                                    ->dehydrated(),

                                Select::make('assigned_employee_id')
                                    ->label('Assigned Employee')
                                    ->relationship(
                                        'assignedEmployee',
                                        'full_name'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(function (): ?int {
                                        $meetingId =
                                            request()->integer('meeting');

                                        return $meetingId
                                            ? Meeting::find($meetingId)
                                                ?->assigned_employee_id
                                            : null;
                                    }),

                                Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Approved' => 'Approved',
                                        'Sent' => 'Sent',
                                        'Accepted' => 'Accepted',
                                        'Rejected' => 'Rejected',
                                        'Expired' => 'Expired',
                                        'Completed' => 'Completed',
                                    ])
                                    ->default('Draft')
                                    ->disabled()
                                    ->dehydrated(false),

                                DatePicker::make('quotation_date')
                                    ->label('Quotation Date')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('valid_until')
                                    ->label('Valid Until'),

                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Services
                |--------------------------------------------------------------------------
                */

                Section::make('Services')
    ->description('Add the services, quantities, pricing and discounts included in this quotation.')
    ->schema([

        Repeater::make('items')
            ->label('Quotation Items')
            ->relationship()
            ->disabled(
                fn ($record): bool => filled($record?->invoice),
            )
            ->defaultItems(1)
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->schema([

                /*
                |--------------------------------------------------------------------------
                | Service Details
                |--------------------------------------------------------------------------
                */

                Grid::make([
                    'default' => 1,
                    'md' => 12,
                ])
                    ->schema([

                        Select::make('service_id')
                            ->label('Service')
                            ->relationship(
                                'service',
                                'service_name'
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Get $get,
                                    Set $set
                                ): void {
                                    if (! $state) {
                                        return;
                                    }

                                    $service = Service::find($state);

                                    if (! $service) {
                                        return;
                                    }

                                    $set(
                                        'description',
                                        $service->description
                                    );

                                    $set(
                                        'unit_price',
                                        (float) $service->standard_price
                                    );

                                    self::calculateLineTotal(
                                        $get,
                                        $set
                                    );
                                }
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 4,
                            ]),

                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'md' => 8,
                            ]),

                    ]),

                /*
                |--------------------------------------------------------------------------
                | Quantity and Pricing
                |--------------------------------------------------------------------------
                */

                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 12,
                ])
                    ->schema([

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->live()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->afterStateUpdated(
                                fn (
                                    Get $get,
                                    Set $set
                                ) =>
                                    self::calculateLineTotal(
                                        $get,
                                        $set
                                    )
                            )
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                            ]),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->live()
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->required()
                            ->afterStateUpdated(
                                fn (
                                    Get $get,
                                    Set $set
                                ) =>
                                    self::calculateLineTotal(
                                        $get,
                                        $set
                                    )
                            )
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                            ]),

                        TextInput::make('discount')
                            ->label('Line Discount')
                            ->live()
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->afterStateUpdated(
                                fn (
                                    Get $get,
                                    Set $set
                                ) =>
                                    self::calculateLineTotal(
                                        $get,
                                        $set
                                    )
                            )
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                            ]),

                        TextInput::make('line_total')
                            ->label('Line Total')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->readOnly()
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 4,
                            ]),

                    ]),

            ])
            ->itemLabel(
                fn (array $state): string =>
                    filled($state['description'] ?? null)
                        ? $state['description']
                        : 'Quotation Item'
            )
            ->addActionLabel('Add Another Service')
            ->columnSpanFull(),

    ])
    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Financial Summary
                |--------------------------------------------------------------------------
                */

                Section::make('Financial Summary')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->live()
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(2),

                                Select::make('discount_type')
                                    ->label('Discount Type')
                                    ->options([
                                        'fixed' => 'Fixed (₹)',
                                        'percentage' => 'Percentage (%)',
                                    ])
                                    ->default('fixed')
                                    ->live()
                                    ->afterStateUpdated(
                                        fn (
                                            Get $get,
                                            Set $set
                                        ) =>
                                            self::calculateFinancialSummary(
                                                $get,
                                                $set,
                                                false
                                            )
                                    )
                                    ->columnSpan(2),

                                TextInput::make('discount_value')
                                    ->label('Discount')
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn (
                                            Get $get,
                                            Set $set
                                        ) =>
                                            self::calculateFinancialSummary(
                                                $get,
                                                $set,
                                                false
                                            )
                                    )
                                    ->columnSpan(2),

                                Toggle::make('tax_applicable')
                                    ->label('Apply GST')
                                    ->live()
                                    ->afterStateUpdated(
                                        fn (
                                            Get $get,
                                            Set $set
                                        ) =>
                                            self::calculateFinancialSummary(
                                                $get,
                                                $set,
                                                false
                                            )
                                    )
                                    ->columnSpan(2),

                                TextInput::make('tax_percentage')
                                    ->label('GST %')
                                    ->numeric()
                                    ->default(18)
                                    ->suffix('%')
                                    ->live()
                                    ->afterStateUpdated(
                                        fn (
                                            Get $get,
                                            Set $set
                                        ) =>
                                            self::calculateFinancialSummary(
                                                $get,
                                                $set,
                                                false
                                            )
                                    )
                                    ->visible(
                                        fn (Get $get): bool =>
                                            (bool) $get('tax_applicable')
                                    )
                                    ->columnSpan(2),

                                TextInput::make('tax')
                                    ->label('GST Amount')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->readOnly()
                                    ->columnSpan(2),

                                TextInput::make('grand_total')
                                    ->label('Grand Total')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->readOnly()
                                    ->columnSpan(2),

                            ]),

                    ])
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                Section::make('Notes')
                    ->schema([

                        Textarea::make('customer_notes')
                            ->label('Customer Notes')
                            ->rows(5),

                        Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(5),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                    ])
                    ->columnSpanFull(),

            ]);
    }
}