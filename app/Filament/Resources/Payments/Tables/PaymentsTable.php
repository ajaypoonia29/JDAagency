<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use App\Services\Communication\CommunicationService;
use App\Services\Finance\PaymentService;
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

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('payment_no')
                    ->label('Payment')
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

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('refunded_amount')
                    ->label('Refunded')
                    ->state(
                        fn (Payment $record): float =>
                            round((float) $record->refunds()
                                ->where('status', 'Processed')
                                ->sum('amount'), 2),
                    )
                    ->money('INR'),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->searchable(),

                TextColumn::make('transaction_reference')
                    ->label('Transaction Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),

                IconColumn::make('receipt_generated')
                    ->label('Receipt')
                    ->boolean(),

                TextColumn::make('receipt_number')
                    ->label('Receipt Number')
                    ->searchable(),

                IconColumn::make('email_sent')
                    ->label('Emailed')
                    ->boolean(),

                TextColumn::make('email_sent_at')
                    ->label('Last Emailed')
                    ->since()
                    ->sortable()
                    ->placeholder('Not sent')
                    ->toggleable(),

                TextColumn::make('customer.primary_email')
                    ->label('Email Recipient')
                    ->placeholder('No email')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email_message_id')
                    ->label('Email Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('whatsapp_sent')
                    ->label('WhatsApp')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->authorize('downloadReceipt')
                    ->visible(
                        fn (Payment $record): bool =>
                            $record->receipt_generated
                            && filled($record->receipt_pdf)
                    )
                    ->url(
                        fn (Payment $record): string =>
                            route(
                                'finance.payments.receipt.download',
                                $record,
                            )
                    )
                    ->openUrlInNewTab(),

                Action::make('downloadStatement')
                    ->label('Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->authorize('downloadStatement')
                    ->visible(
                        fn (Payment $record): bool =>
                            $record->receipt_generated
                    )
                    ->url(
                        fn (Payment $record): string =>
                            route(
                                'finance.payments.statement.download',
                                $record,
                            )
                    )
                    ->openUrlInNewTab(),

                Action::make('sendWhatsapp')
                    ->label(
                        fn (Payment $record): string =>
                            $record->whatsapp_sent
                                ? 'Resend WhatsApp'
                                : 'Send WhatsApp'
                    )
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('sendReceipt')
                    ->visible(
                        fn (Payment $record): bool =>
                            $record->receipt_generated
                    )
                    ->action(function (Payment $record): void {

                        $sent = CommunicationService::sendReceiptViaWhatsApp(
                            $record
                        );

                        if ($sent) {
                            Notification::make()
                                ->title('Receipt sent via WhatsApp.')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Unable to send receipt via WhatsApp.')
                            ->danger()
                            ->send();

                    }),

                Action::make('sendEmail')
                    ->label(
                        fn (Payment $record): string =>
                            $record->email_sent
                                ? 'Resend Email'
                                : 'Send Email'
                    )
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('sendReceipt')
                    ->visible(
                        fn (Payment $record): bool =>
                            $record->receipt_generated
                    )
                    ->modalHeading(
                        fn (Payment $record): string =>
                            $record->email_sent
                                ? 'Resend Payment Receipt'
                                : 'Send Payment Receipt'
                    )
                    ->modalDescription(
                        fn (Payment $record): string =>
                            'Send the receipt to: '
                            . (
                                $record->customer?->primary_email
                                ?? 'No customer email available'
                            )
                    )
                    ->action(function (Payment $record): void {

                        $sent = CommunicationService::sendReceiptViaEmail(
                            $record
                        );

                        if ($sent) {
                            $record->refresh();

                            Notification::make()
                                ->title('Receipt emailed successfully.')
                                ->body(
                                    'Sent to: '
                                    . (
                                        $record->customer?->primary_email
                                        ?? 'Customer email'
                                    )
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Unable to send receipt email.')
                            ->body(
                                blank($record->customer?->primary_email)
                                    ? 'The customer does not have a primary email address.'
                                    : 'Check the application log for the mail delivery error.'
                            )
                            ->danger()
                            ->send();

                    }),

                EditAction::make()
                    ->visible(
                        fn (Payment $record): bool =>
                            ! $record->refunds()
                                ->where('status', 'Processed')
                                ->exists(),
                    ),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->using(
                            fn (Payment $record): bool =>
                                app(PaymentService::class)->delete($record)
                        ),

                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->using(
                            fn (Payment $record): bool =>
                                app(PaymentService::class)->forceDelete($record)
                        ),

                    RestoreBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->using(
                            fn (Payment $record): bool =>
                                app(PaymentService::class)->restore($record)
                        ),

                ]),

            ]);
    }
}