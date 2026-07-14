<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Communication\EmailService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\QuotationWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        /** @var Meeting $record */
        return app(MeetingWorkflowService::class)
            ->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateQuotation')
                ->label('Generate Quotation')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        $this->record->status === 'Completed'
                        && in_array(
                            $this->record->outcome,
                            ['Quotation Required', 'Converted'],
                            true,
                        )
                        && ! $this->record->hasQuotation()
                        && Gate::allows('create', Quotation::class)
                )
                ->url(
                    fn (): string =>
                        QuotationResource::getUrl('create', [
                            'meeting' => $this->record->id,
                        ])
                ),

            Action::make('openQuotation')
                ->label(
                    fn (): string =>
                        $this->quotation()
                            ? 'Open '
                                . $this->quotation()->quotation_code
                            : 'Open Quotation'
                )
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(
                    fn (): bool =>
                        $this->quotation() !== null
                        && Gate::allows(
                            'view',
                            $this->quotation(),
                        )
                )
                ->url(
                    fn (): string =>
                        QuotationResource::getUrl('edit', [
                            'record' => $this->quotation(),
                        ])
                ),

            Action::make('approveQuotation')
                ->label('Approve Quotation')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->quotation()?->status === 'Draft'
                        && Gate::allows(
                            'approve',
                            $this->quotation(),
                        )
                )
                ->action(function (): void {
                    $quotation = $this->quotation();

                    if (! $quotation) {
                        return;
                    }

                    Gate::authorize('approve', $quotation);

                    app(QuotationWorkflowService::class)
                        ->approve($quotation);

                    Notification::make()
                        ->success()
                        ->title('Quotation approved successfully.')
                        ->send();
                }),

            Action::make('sendQuotation')
                ->label(
                    fn (): string =>
                        ($this->quotation()?->quotation_send_count ?? 0) > 0
                            ? 'Resend Quotation'
                            : 'Send Quotation'
                )
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription(
                    fn (): string =>
                        'Send the quotation to: '
                        . (
                            $this->quotation()
                                ?->customer
                                ?->primary_email
                            ?? 'No customer email available'
                        )
                )
                ->visible(function (): bool {
                    $quotation = $this->quotation();

                    return $quotation !== null
                        && in_array(
                            $quotation->status,
                            [
                                'Approved',
                                'Sent',
                                'Accepted',
                                'Completed',
                            ],
                            true,
                        )
                        && Gate::allows('send', $quotation);
                })
                ->action(function (): void {
                    $quotation = $this->quotation();

                    if (! $quotation) {
                        return;
                    }

                    Gate::authorize('send', $quotation);

                    $sent = app(QuotationWorkflowService::class)
                        ->send($quotation);

                    $notification = Notification::make()
                        ->title(
                            $sent
                                ? 'Quotation emailed successfully.'
                                : 'Unable to send quotation.'
                        )
                        ->body(
                            $sent
                                ? 'Lead status advanced without regression.'
                                : (
                                    blank(
                                        $quotation
                                            ->customer
                                            ?->primary_email
                                    )
                                        ? 'The customer has no primary email address.'
                                        : 'Check the application log for the mail error.'
                                )
                        );

                    $sent
                        ? $notification->success()
                        : $notification->danger();

                    $notification->send();
                }),

            Action::make('receivePayment')
                ->label('Receive Payment')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(function (): bool {
                    $quotation = $this->quotation();

                    return $quotation !== null
                        && in_array(
                            $quotation->status,
                            [
                                'Approved',
                                'Sent',
                                'Accepted',
                                'Completed',
                            ],
                            true,
                        )
                        && $quotation->payment_status !== 'Paid'
                        && Gate::allows('create', Payment::class);
                })
                ->url(
                    fn (): string =>
                        PaymentResource::getUrl('create', [
                            'quotation' => $this->quotation()?->id,
                        ])
                ),

            Action::make('openPayment')
                ->label(
                    fn (): string =>
                        $this->latestPayment()
                            ? 'Open '
                                . $this->latestPayment()->payment_no
                            : 'Open Payment'
                )
                ->icon('heroicon-o-credit-card')
                ->color('gray')
                ->visible(
                    fn (): bool =>
                        $this->latestPayment() !== null
                        && Gate::allows(
                            'view',
                            $this->latestPayment(),
                        )
                )
                ->url(
                    fn (): string =>
                        PaymentResource::getUrl('edit', [
                            'record' => $this->latestPayment(),
                        ])
                ),

            Action::make('downloadReceipt')
                ->label('Open Receipt')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(function (): bool {
                    $payment = $this->latestPayment();

                    return $payment !== null
                        && $payment->receipt_generated
                        && filled($payment->receipt_pdf)
                        && Gate::allows(
                            'downloadReceipt',
                            $payment,
                        );
                })
                ->url(
                    fn (): string =>
                        route(
                            'finance.payments.receipt.download',
                            $this->latestPayment(),
                        )
                )
                ->openUrlInNewTab(),

            Action::make('sendReceipt')
                ->label(
                    fn (): string =>
                        $this->latestPayment()?->email_sent
                            ? 'Resend Receipt'
                            : 'Send Receipt'
                )
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(function (): bool {
                    $payment = $this->latestPayment();

                    return $payment !== null
                        && $payment->receipt_generated
                        && Gate::allows(
                            'sendReceipt',
                            $payment,
                        );
                })
                ->action(function (): void {
                    $payment = $this->latestPayment();

                    if (! $payment) {
                        return;
                    }

                    Gate::authorize('sendReceipt', $payment);

                    $sent = EmailService::sendReceipt($payment);

                    $notification = Notification::make()
                        ->title(
                            $sent
                                ? 'Receipt emailed successfully.'
                                : 'Unable to send receipt.'
                        );

                    $sent
                        ? $notification->success()
                        : $notification->danger();

                    $notification->send();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function quotation(): ?Quotation
    {
        return $this->record
            ->quotations()
            ->with([
                'customer',
                'payments',
            ])
            ->latest('id')
            ->first();
    }

    protected function latestPayment(): ?Payment
    {
        return $this->quotation()
            ?->payments()
            ->with('customer')
            ->latest('id')
            ->first();
    }
}
