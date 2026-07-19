<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\CRM\QuotationWorkflowService;
use App\Services\Finance\InvoiceService;
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
use Illuminate\Support\Facades\Gate;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_code')
                    ->label('Quotation')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lead.lead_code')
                    ->label('Lead')
                    ->placeholder('Standalone')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meeting.meeting_code')
                    ->label('Meeting')
                    ->placeholder('Direct')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quotation_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('balance_due')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('quotation_sent_at')
                    ->label('Last Emailed')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quotation_send_count')
                    ->label('Emails')
                    ->badge()
                    ->sortable(),

                TextColumn::make('last_sent_to')
                    ->label('Last Recipient')
                    ->searchable()
                    ->toggleable(
                        isToggledHiddenByDefault: true,
                    ),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('createInvoice')
                    ->label('Create Invoice')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(
                        fn (Quotation $record): bool =>
                            ! $record->invoice()->exists()
                            && in_array(
                                $record->status,
                                ['Approved', 'Sent', 'Accepted', 'Completed'],
                                true,
                            )
                            && auth()->user()?->can(
                                'create',
                                Invoice::class,
                            ) === true,
                    )
                    ->action(function (Quotation $record) {
                        Gate::authorize('create', Invoice::class);

                        $invoice = app(InvoiceService::class)
                            ->createFromQuotation($record);

                        Notification::make()
                            ->title('Invoice draft created.')
                            ->success()
                            ->send();

                        return redirect()->to(
                            InvoiceResource::getUrl('edit', [
                                'record' => $invoice,
                            ]),
                        );
                    }),

                Action::make('viewInvoice')
                    ->label('View Invoice')
                    ->icon('heroicon-o-document-text')
                    ->visible(
                        fn (Quotation $record): bool =>
                            $record->invoice()->exists()
                            && auth()->user()?->can(
                                'view',
                                $record->invoice,
                            ) === true,
                    )
                    ->url(
                        fn (Quotation $record): string =>
                            InvoiceResource::getUrl('view', [
                                'record' => $record->invoice,
                            ]),
                    ),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(
                        fn (Quotation $record): bool =>
                            $record->status === 'Draft'
                    )
                    ->action(function (
                        Quotation $record,
                    ): void {
                        app(QuotationWorkflowService::class)
                            ->approve($record);

                        Notification::make()
                            ->title('Quotation approved.')
                            ->success()
                            ->send();
                    }),

                Action::make('receivePayment')
                    ->label('Receive Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->authorize('receivePayment')
                    ->visible(
                        fn (Quotation $record): bool =>
                            in_array(
                                $record->status,
                                [
                                    'Approved',
                                    'Sent',
                                    'Accepted',
                                    'Completed',
                                ],
                                true,
                            )
                            && $record->payment_status !== 'Paid'
                    )
                    ->url(
                        fn (Quotation $record): string =>
                            PaymentResource::getUrl('create', [
                                'quotation' => $record->id,
                            ])
                    ),

                Action::make('sendQuotation')
                    ->label(
                        fn (Quotation $record): string =>
                            $record->quotation_send_count > 0
                                ? 'Resend Email'
                                : 'Send Email'
                    )
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('send')
                    ->visible(
                        fn (Quotation $record): bool =>
                            in_array(
                                $record->status,
                                [
                                    'Approved',
                                    'Sent',
                                    'Accepted',
                                    'Completed',
                                ],
                                true,
                            )
                    )
                    ->modalDescription(
                        fn (Quotation $record): string =>
                            'Send quotation to: '
                            . (
                                $record
                                    ->customer
                                    ?->primary_email
                                ?? 'No email available'
                            )
                    )
                    ->action(function (
                        Quotation $record,
                    ): void {
                        $sent = app(
                            QuotationWorkflowService::class
                        )->send($record);

                        $notification = Notification::make()
                            ->title(
                                $sent
                                    ? 'Quotation emailed successfully.'
                                    : 'Unable to send quotation email.'
                            );

                        $sent
                            ? $notification->success()
                            : $notification->danger();

                        $notification->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(),
                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords(),
                    RestoreBulkAction::make()
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }
}
