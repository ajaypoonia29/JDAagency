<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\Quotation;
use App\Models\Service;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{

protected static function calculateLineTotal(Get $get, Set $set): void
{
    $quantity = (float) ($get('quantity') ?? 0);
    $unitPrice = (float) ($get('unit_price') ?? 0);
    $discount = (float) ($get('discount') ?? 0);

    $lineTotal = ($quantity * $unitPrice) - $discount;

    $set('line_total', max(0, $lineTotal));
}

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Quotation Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('quotation_code')
                                    ->default(fn () => Quotation::nextQuotationCode())
                                    ->readOnly()
                                    ->required(),

                                Select::make('lead_id')
                                    ->relationship('lead', 'company_name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('customer_id')
                                    ->relationship('customer', 'display_name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('meeting_id')
                                    ->relationship('meeting', 'meeting_title')
                                    ->searchable()
                                    ->preload(),

                                Select::make('assigned_employee_id')
                                    ->relationship('assignedEmployee', 'full_name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Sent' => 'Sent',
                                        'Accepted' => 'Accepted',
                                        'Rejected' => 'Rejected',
                                        'Expired' => 'Expired',
                                        'Converted' => 'Converted',
                                    ])
                                    ->default('Draft')
                                    ->required(),

                                DatePicker::make('quotation_date')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('valid_until'),

                            ]),

                    ]),
                Section::make('Services')
    ->schema([

        Repeater::make('items')
            ->relationship()
            ->defaultItems(1)
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->schema([

                Grid::make(6)
                    ->schema([

                        Select::make('service_id')
    ->relationship('service', 'service_name')
    ->searchable()
    ->preload()
    ->live()
    ->required()
    ->afterStateUpdated(function (?string $state, Get $get, Set $set) {

    if (! $state) {
        return;
    }

    $service = Service::find($state);

    if (! $service) {
        return;
    }

    $set('description', $service->description);
    $set('unit_price', (float) $service->standard_price);

    self::calculateLineTotal($get, $set);

})
    ->columnSpan(2),

                        TextInput::make('description')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('quantity')
    ->live()
    ->numeric()
    ->default(1)
    ->required()
    ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),

                        TextInput::make('unit_price')
    ->live()
    ->numeric()
    ->prefix('₹')
    ->default(0)
    ->required()
    ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),

                        TextInput::make('discount')
    ->live()
    ->numeric()
    ->prefix('₹')
    ->default(0)
    ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateLineTotal($get, $set)),

                        TextInput::make('line_total')
    ->numeric()
    ->prefix('₹')
    ->default(0)
    ->readOnly(),

                    ]),

            ])
            ->columnSpanFull(),

    ]),

                Section::make('Financial Summary')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0)
                                    ->readOnly(),

                                TextInput::make('discount')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0),

                                TextInput::make('tax')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0),

                                TextInput::make('grand_total')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->default(0)
                                    ->readOnly(),

                            ]),

                    ]),

                Section::make('Notes')
                    ->schema([

                        Textarea::make('customer_notes')
                            ->rows(4),

                        Textarea::make('internal_notes')
                            ->rows(4),

                    ]),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->default(true),

                    ]),

            ]);
    }
}