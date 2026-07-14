<?php

declare(strict_types=1);

namespace App\Filament\Resources\Refunds\Schemas;

use App\Models\Refund;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Refund')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('number')
                            ->content(fn (?Refund $record): string =>
                                $record?->refund_no ?? '-'),
                        Placeholder::make('invoice')
                            ->content(fn (?Refund $record): string =>
                                $record?->invoice?->invoice_no ?? '-'),
                        Placeholder::make('status')
                            ->content(fn (?Refund $record): string =>
                                $record?->status ?? '-'),
                        Placeholder::make('payment')
                            ->content(fn (?Refund $record): string =>
                                $record?->payment?->payment_no ?? '-'),
                        Placeholder::make('date')
                            ->content(fn (?Refund $record): string =>
                                $record?->refund_date?->format('d M Y') ?? '-'),
                        Placeholder::make('amount')
                            ->content(fn (?Refund $record): string =>
                                'INR ' . number_format(
                                    (float) ($record?->amount ?? 0),
                                    2,
                                )),
                    ]),
                    Placeholder::make('method')
                        ->content(fn (?Refund $record): string =>
                            $record?->refund_method ?? '-'),
                    Placeholder::make('reason')
                        ->content(fn (?Refund $record): string =>
                            $record?->reason ?? '-'),
                    Placeholder::make('cancel_reason')
                        ->content(fn (?Refund $record): string =>
                            $record?->cancel_reason ?? '-'),
                ]),
        ]);
    }
}
