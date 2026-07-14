<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Communication\CommunicationService;
use App\Services\Finance\CreditNoteService;
use App\Services\Finance\InvoiceLedgerService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\RefundService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
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
                TextColumn::make('invoice_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('grand_total')
                    ->label('Original Total')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('credited_total')
                    ->label('Credits')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('net_total')
                    ->label('Net Total')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('refunded_total')
                    ->label('Refunded')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Net Paid')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->money('INR')
                    ->sortable(),
                IconColumn::make('email_sent')
                    ->label('Emailed')
                    ->boolean(),
                TextColumn::make('email_sent_at')
                    ->label('Last Emailed')
                    ->since()
                    ->placeholder('Not sent')
                    ->toggleable(),
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

                Action::make('sendEmail')
                    ->label(
                        fn (Invoice $record): string =>
                            $record->email_sent
                                ? 'Resend Invoice'
                                : 'Email Invoice',
                    )
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('send')
                    ->visible(
                        fn (Invoice $record): bool =>
                            $record->issued_at !== null
                            && $record->status !== 'Void'
                            && filled($record->invoice_pdf),
                    )
                    ->modalDescription(
                        fn (Invoice $record): string =>
                            'Send the invoice to: '
                            . ($record->customer?->primary_email
                                ?? 'No customer email available'),
                    )
                    ->action(function (Invoice $record): void {
                        $sent = CommunicationService::sendInvoiceViaEmail(
                            $record,
                        );

                        $record->refresh();

                        $notification = Notification::make()
                            ->title($sent
                                ? 'Invoice emailed successfully.'
                                : 'Unable to send invoice email.')
                            ->body($sent
                                ? 'Sent to: ' . $record->last_sent_to
                                : ($record->last_delivery_error
                                    ?: 'Check the application log.'));

                        if ($sent) {
                            $notification->success();
                        } else {
                            $notification->danger();
                        }

                        $notification->send();
                    }),

                Action::make('creditNote')
                    ->label('Credit Note')
                    ->icon('heroicon-o-document-minus')
                    ->color('warning')
                    ->authorize('createCreditNote')
                    ->visible(
                        fn (Invoice $record): bool =>
                            $record->issued_at !== null
                            && $record->status !== 'Void'
                            && (float) $record->credited_total
                                < (float) $record->grand_total,
                    )
                    ->schema([
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('tax')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        DatePicker::make('issue_date')
                            ->default(now())
                            ->required(),
                        TextInput::make('description')
                            ->maxLength(255),
                        Textarea::make('reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (
                        Invoice $record,
                        array $data,
                    ): void {
                        $creditNote = app(CreditNoteService::class)
                            ->createAndIssue($record, $data);

                        Notification::make()
                            ->title(
                                'Credit note '
                                . $creditNote->credit_note_no
                                . ' issued.',
                            )
                            ->success()
                            ->send();
                    }),

                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->authorize('refund')
                    ->visible(
                        fn (Invoice $record): bool =>
                            $record->issued_at !== null
                            && $record->status !== 'Void'
                            && $record->refundableAmount() > 0,
                    )
                    ->schema([
                        Select::make('payment_id')
                            ->label('Payment')
                            ->options(fn (Invoice $record): array =>
                                self::paymentOptions($record))
                            ->searchable()
                            ->required(),
                        Select::make('credit_note_id')
                            ->label('Credit Note (optional)')
                            ->options(fn (Invoice $record): array =>
                                CreditNote::query()
                                    ->where('invoice_id', $record->getKey())
                                    ->where('status', 'Issued')
                                    ->orderBy('credit_note_no')
                                    ->pluck('credit_note_no', 'id')
                                    ->all())
                            ->searchable(),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        Select::make('refund_method')
                            ->options([
                                'Original Method' => 'Original Method',
                                'Cash' => 'Cash',
                                'UPI' => 'UPI',
                                'Bank Transfer' => 'Bank Transfer',
                                'Cheque' => 'Cheque',
                                'Credit Card' => 'Credit Card',
                                'Debit Card' => 'Debit Card',
                            ])
                            ->default('Original Method')
                            ->required(),
                        TextInput::make('transaction_reference')
                            ->maxLength(255),
                        DatePicker::make('refund_date')
                            ->default(now())
                            ->required(),
                        Textarea::make('reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (
                        Invoice $record,
                        array $data,
                    ): void {
                        $refund = app(RefundService::class)
                            ->process($record, $data);

                        Notification::make()
                            ->title(
                                'Refund '
                                . $refund->refund_no
                                . ' processed.',
                            )
                            ->success()
                            ->send();
                    }),

                Action::make('refreshLedger')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->authorize('view')
                    ->action(function (Invoice $record): void {
                        app(InvoiceLedgerService::class)->recalculate($record);

                        Notification::make()
                            ->title('Invoice ledger refreshed.')
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
                                ['Paid', 'Credited', 'Refunded', 'Void'],
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

    /** @return array<int|string, string> */
    private static function paymentOptions(Invoice $invoice): array
    {
        return Payment::query()
            ->where('quotation_id', $invoice->quotation_id)
            ->orderBy('payment_no')
            ->get()
            ->mapWithKeys(function (Payment $payment): array {
                $refunded = (float) $payment->refunds()
                    ->where('status', 'Processed')
                    ->sum('amount');
                $available = max((float) $payment->amount - $refunded, 0);

                if ($available <= 0) {
                    return [];
                }

                return [
                    $payment->getKey() => sprintf(
                        '%s — ₹ %s available',
                        $payment->payment_no,
                        number_format($available, 2),
                    ),
                ];
            })
            ->all();
    }
}
