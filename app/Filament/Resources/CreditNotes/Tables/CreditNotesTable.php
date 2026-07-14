<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditNotes\Tables;

use App\Models\CreditNote;
use App\Services\Finance\CreditNoteService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_note_no')
                    ->label('Credit Note')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_no')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('issue_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('grand_total')->money('INR')->sortable(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authorize('download')
                    ->visible(
                        fn (CreditNote $record): bool =>
                            filled($record->credit_note_pdf),
                    )
                    ->url(
                        fn (CreditNote $record): string =>
                            route('finance.credit-notes.download', $record),
                    )
                    ->openUrlInNewTab(),
                Action::make('void')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->authorize('void')
                    ->visible(
                        fn (CreditNote $record): bool =>
                            $record->status === 'Issued',
                    )
                    ->schema([
                        Textarea::make('reason')->required()->rows(3),
                    ])
                    ->action(function (
                        CreditNote $record,
                        array $data,
                    ): void {
                        app(CreditNoteService::class)->void(
                            $record,
                            (string) $data['reason'],
                        );

                        Notification::make()
                            ->title('Credit note voided.')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
            ]);
    }
}
