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
                    Grid::make(4)->schema([
                        Placeholder::make('original_total_display')
                            ->label('Original Total')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->grand_total)),
                        Placeholder::make('credited_display')
                            ->label('Credit Notes')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->credited_total)),
                        Placeholder::make('net_total_display')
                            ->label('Net Invoice Total')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->net_total)),
                        Placeholder::make('refunded_display')
                            ->label('Refunded')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->refunded_total)),
                        Placeholder::make('paid_display')
                            ->label('Net Paid')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->total_paid)),
                        Placeholder::make('balance_display')
                            ->label('Balance Due')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->balance_due)),
                        Placeholder::make('subtotal_display')
                            ->label('Subtotal')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->subtotal)),
                        Placeholder::make('tax_display')
                            ->label('Tax')
                            ->content(fn (?Invoice $record): string =>
                                self::money($record?->tax)),
                    ]),
                ]),

            Section::make('Delivery')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('email_sent_display')
                            ->label('Email Status')
                            ->content(fn (?Invoice $record): string =>
                                $record?->email_sent ? 'Sent' : 'Not sent'),
                        Placeholder::make('last_sent_to_display')
                            ->label('Last Recipient')
                            ->content(fn (?Invoice $record): string =>
                                $record?->last_sent_to ?? '-'),
                        Placeholder::make('email_count_display')
                            ->label('Send Count')
                            ->content(fn (?Invoice $record): string =>
                                (string) ($record?->email_send_count ?? 0)),
                        Placeholder::make('email_sent_at_display')
                            ->label('Last Sent')
                            ->content(fn (?Invoice $record): string =>
                                $record?->email_sent_at?->format('d M Y H:i')
                                ?? '-'),
                    ]),
                    Placeholder::make('delivery_error_display')
                        ->label('Last Delivery Error')
                        ->content(fn (?Invoice $record): string =>
                            $record?->last_delivery_error ?? '-'),
                ]),
        ]);
    }

    private static function money(mixed $value): string
    {
        return 'INR ' . number_format((float) ($value ?? 0), 2);
    }
}
