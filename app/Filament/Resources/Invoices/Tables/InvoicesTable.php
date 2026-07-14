<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Services\Finance\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quotation.quotation_code')
                    ->label('Quotation')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->money('INR')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Issue')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('issue')
                    ->visible(
                        fn (Invoice $record): bool =>
                            $record->status === 'Draft',
                    )
                    ->action(function (Invoice $record): void {
                        app(InvoiceService::class)->issue($record);

                        Notification::make()
                            ->title('Invoice issued successfully.')
                            ->success()
                            ->send();
                    }),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authorize('download')
                    ->visible(
                        fn (Invoice $record): bool =>
                            filled($record->invoice_pdf),
                    )
                    ->url(
                        fn (Invoice $record): string =>
                            route('finance.invoices.download', $record),
                    )
                    ->openUrlInNewTab(),

                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('void')
                    ->visible(
                        fn (Invoice $record): bool =>
                            ! in_array(
                                $record->status,
                                ['Paid', 'Void'],
                                true,
                            ),
                    )
                    ->schema([
                        Textarea::make('reason')
                            ->label('Void Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (
                        Invoice $record,
                        array $data,
                    ): void {
                        app(InvoiceService::class)->void(
                            $record,
                            (string) $data['reason'],
                        );

                        Notification::make()
                            ->title('Invoice voided.')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(
                        fn (Invoice $record): bool =>
                            $record->status === 'Draft',
                    ),
            ]);
    }
}
