<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use App\Services\Communication\CommunicationService;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('payment_no')
                    ->searchable(),

                TextColumn::make('quotation.quotation_code')
                    ->label('Quotation')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->searchable(),

                TextColumn::make('transaction_reference')
                    ->searchable(),

                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),

                IconColumn::make('receipt_generated')
                    ->boolean(),

                TextColumn::make('receipt_number')
                    ->searchable(),

                IconColumn::make('whatsapp_sent')
                    ->boolean(),

                IconColumn::make('email_sent')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
                TrashedFilter::make(),
            ])

            ->recordActions([

                Action::make('downloadReceipt')
    ->label('Receipt')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->visible(fn ($record): bool => $record->receipt_generated)
    ->url(fn ($record): string => Storage::url($record->receipt_pdf))
    ->openUrlInNewTab(),

Action::make('downloadStatement')
    ->label('Statement')
    ->icon('heroicon-o-document-text')
    ->color('info')
    ->visible(fn ($record): bool => $record->receipt_generated)
    ->action(function ($record): void {

        if (
            empty($record->statement_pdf) ||
            ! Storage::disk('public')->exists($record->statement_pdf)
        ) {
            \App\Services\Documents\DocumentService::paymentStatement($record);

            $record->refresh();
        }

        redirect(Storage::url($record->statement_pdf));

    }),
                Action::make('sendWhatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->receipt_generated)
                    ->action(function ($record): void {

    CommunicationService::sendReceiptViaWhatsApp($record);

    Notification::make()
        ->title('Receipt marked as sent via WhatsApp.')
        ->success()
        ->send();

}),

                Action::make('sendEmail')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->receipt_generated)
                    ->action(function ($record): void {

    CommunicationService::sendReceiptViaEmail($record);

    Notification::make()
        ->title('Receipt marked as sent via Email.')
        ->success()
        ->send();

}),

                EditAction::make(),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                    ForceDeleteBulkAction::make(),

                    RestoreBulkAction::make(),

                ]),

            ]);
    }
}