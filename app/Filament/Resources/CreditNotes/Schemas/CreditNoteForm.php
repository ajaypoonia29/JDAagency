<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditNotes\Schemas;

use App\Models\CreditNote;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Credit Note')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('number')
                            ->content(fn (?CreditNote $record): string =>
                                $record?->credit_note_no ?? '-'),
                        Placeholder::make('invoice')
                            ->content(fn (?CreditNote $record): string =>
                                $record?->invoice?->invoice_no ?? '-'),
                        Placeholder::make('status')
                            ->content(fn (?CreditNote $record): string =>
                                $record?->status ?? '-'),
                        Placeholder::make('customer')
                            ->content(fn (?CreditNote $record): string =>
                                $record?->customer?->display_name ?? '-'),
                        Placeholder::make('issue_date')
                            ->content(fn (?CreditNote $record): string =>
                                $record?->issue_date?->format('d M Y') ?? '-'),
                        Placeholder::make('amount')
                            ->content(fn (?CreditNote $record): string =>
                                'INR ' . number_format(
                                    (float) ($record?->grand_total ?? 0),
                                    2,
                                )),
                    ]),
                    Placeholder::make('reason')
                        ->content(fn (?CreditNote $record): string =>
                            $record?->reason ?? '-'),
                ]),
        ]);
    }
}
