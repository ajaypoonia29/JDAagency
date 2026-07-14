<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Identity')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('invoice_number_display')
                            ->label('Invoice Number')
                            ->content(
                                fn (?Invoice $record): string =>
                                    $record?->invoice_no ?? 'Generated automatically',
                            ),
                        Placeholder::make('quotation_display')
                            ->label('Quotation')
                            ->content(
                                fn (?Invoice $record): string =>
                                    $record?->quotation?->quotation_code ?? '-',
                            ),
                        Placeholder::make('customer_display')
                            ->label('Customer')
                            ->content(
                                fn (?Invoice $record): string =>
                                    $record?->customer?->display_name ?? '-',
                            ),
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(
                                fn (?Invoice $record): string =>
                                    $record?->status ?? 'Draft',
                            ),
                    ]),
                ]),

            Section::make('Dates and Notes')
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('invoice_date')
                            ->required()
                            ->disabled(
                                fn (?Invoice $record): bool =>
                                    filled($record)
                                    && $record->status !== 'Draft',
                            )
                            ->dehydrated(),
                        DatePicker::make('due_date')
                            ->required()
                            ->disabled(
                                fn (?Invoice $record): bool =>
                                    filled($record)
                                    && $record->status !== 'Draft',
                            )
                            ->dehydrated(),
                    ]),
                    Textarea::make('customer_notes')
                        ->rows(3)
                        ->disabled(
                            fn (?Invoice $record): bool =>
                                filled($record)
                                && $record->status !== 'Draft',
                        )
                        ->dehydrated(),
                    Textarea::make('internal_notes')
                        ->rows(3)
                        ->disabled(
                            fn (?Invoice $record): bool =>
                                filled($record)
                                && $record->status !== 'Draft',
                        )
                        ->dehydrated(),
                    Toggle::make('is_active')
                        ->default(true)
                        ->disabled(
                            fn (?Invoice $record): bool =>
                                filled($record)
                                && $record->status !== 'Draft',
                        )
                        ->dehydrated(),
                ]),

            Section::make('Financial Snapshot')
                ->schema([
                    Grid::make(5)->schema([
                        Placeholder::make('subtotal_display')
                            ->label('Subtotal')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->subtotal)),
                        Placeholder::make('discount_display')
                            ->label('Discount')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->discount_value)),
                        Placeholder::make('tax_display')
                            ->label('Tax')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->tax)),
                        Placeholder::make('paid_display')
                            ->label('Paid')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->total_paid)),
                        Placeholder::make('balance_display')
                            ->label('Balance Due')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->balance_due)),
                    ]),
                    Placeholder::make('grand_total_display')
                        ->label('Grand Total')
                        ->content(fn (?Invoice $record): string =>
                            self::money($record?->grand_total)),
                ]),
        ]);
    }

    private static function money(mixed $value): string
    {
        return 'INR ' . number_format((float) ($value ?? 0), 2);
    }
}
