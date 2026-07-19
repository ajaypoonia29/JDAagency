<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\Quotation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class SalesJourneyService
{
    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     state: string,
     *     detail: string
     * }>
     */
    public function stages(Lead $lead): array
    {
        $meeting = $this->latestMeeting($lead);
        $quotation = $this->latestQuotation($lead);
        $invoice = $this->latestInvoice($lead);
        $paid = $this->paidAmount($lead);

        $leadStarted = filled($lead->lead_status);
        $meetingCompleted = $meeting?->status === 'Completed';
        $quotationStarted = $quotation !== null;
        $quotationCompleted = in_array(
            $quotation?->status,
            ['Approved', 'Sent', 'Accepted', 'Completed'],
            true,
        );
        $invoiceStarted = $invoice !== null;
        $invoiceCompleted = in_array(
            $invoice?->status,
            [
                'Issued',
                'Partially Paid',
                'Paid',
                'Overdue',
                'Credited',
                'Refunded',
            ],
            true,
        );
        $paymentStarted = $paid > 0;
        $paymentCompleted = $invoice
            ? (
                $invoice->issued_at !== null
                && $invoice->status !== 'Void'
                && (float) $invoice->balance_due <= 0
            )
            : $quotation?->payment_status === 'Paid';
        $completed = $lead->lead_status === 'Won'
            && (
                $invoice === null
                || $paymentCompleted
            );

        return [
            $this->stage(
                'lead',
                'Lead',
                $leadStarted ? 'completed' : 'current',
                (string) ($lead->lead_status ?: 'New'),
            ),
            $this->stage(
                'meeting',
                'Meeting',
                $this->state(
                    $meeting !== null,
                    $meetingCompleted,
                    ! $meeting,
                ),
                $meeting
                    ? sprintf('%s · %s', $meeting->status, $meeting->outcome)
                    : 'No meeting scheduled',
            ),
            $this->stage(
                'quotation',
                'Quotation',
                $this->state(
                    $quotationStarted,
                    $quotationCompleted,
                    $meetingCompleted && ! $quotation,
                ),
                $quotation
                    ? sprintf(
                        '%s · ₹%s',
                        $quotation->status,
                        number_format((float) $quotation->grand_total, 2),
                    )
                    : 'No quotation created',
            ),
            $this->stage(
                'invoice',
                'Invoice',
                $this->state(
                    $invoiceStarted,
                    $invoiceCompleted,
                    $quotationCompleted && ! $invoice,
                ),
                $invoice
                    ? sprintf(
                        '%s · Balance ₹%s',
                        $invoice->status,
                        number_format((float) $invoice->balance_due, 2),
                    )
                    : 'No invoice created',
            ),
            $this->stage(
                'payment',
                'Payment',
                $this->state(
                    $paymentStarted,
                    $paymentCompleted,
                    $invoiceCompleted && ! $paymentStarted,
                ),
                sprintf('Received ₹%s', number_format($paid, 2)),
            ),
            $this->stage(
                'complete',
                'Complete',
                $completed ? 'completed' : 'pending',
                $completed
                    ? 'Lead won and sales journey completed'
                    : 'Waiting for financial completion',
            ),
        ];
    }

    public function displayLeadStatus(Lead $lead): string
    {
        if (
            $lead->lead_status === 'Meeting Scheduled'
            && $this->latestMeeting($lead)?->status === 'Completed'
        ) {
            return 'Meeting Completed';
        }

        return (string) ($lead->lead_status ?: 'New');
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     tone: string
     * }
     */
    public function nextAction(Lead $lead): array
    {
        if ($lead->lead_status === 'Lost') {
            return $this->action(
                'closed-lost',
                'Lead closed as lost',
                'No automatic action is available for a terminal lost lead.',
                'danger',
            );
        }


        $meeting = $this->latestMeeting($lead);
        $quotation = $this->latestQuotation($lead);
        $invoice = $this->latestInvoice($lead);

        /*
         * Existing downstream records take precedence over earlier CRM stages.
         * This keeps imported, recovered, and partially completed journeys from
         * being sent backwards to "Schedule meeting".
         */
        if ($invoice) {
            if ($invoice->status === 'Draft') {
                return $this->action(
                    'issue-invoice',
                    'Issue invoice',
                    (float) $invoice->total_paid > 0
                        ? 'Issue the draft invoice and reconcile its allocated payments.'
                        : 'Issue the draft invoice before collecting payment.',
                    'warning',
                );
            }

            if ($invoice->status === 'Void') {
                return $this->action(
                    'review-void-invoice',
                    'Review void invoice',
                    'The quotation has a void invoice and requires finance review.',
                    'danger',
                );
            }

            if ((float) $invoice->balance_due > 0) {
                return $this->action(
                    'receive-payment',
                    'Receive payment',
                    sprintf(
                        'Collect the remaining balance of ₹%s.',
                        number_format(
                            (float) $invoice->balance_due,
                            2,
                        ),
                    ),
                    'success',
                );
            }

            if ($lead->lead_status === 'Won') {
                return $this->action(
                    'completed',
                    'Sales journey completed',
                    'The lead, quotation, invoice, and payment lifecycle is complete.',
                    'success',
                );
            }

            return $this->action(
                'synchronize',
                'Synchronize completion',
                'The financial balance is settled; refresh the final CRM state.',
                'success',
            );
        }

        if ($lead->lead_status === 'Won') {
            return $this->action(
                'completed',
                'Sales journey completed',
                'The lead is terminal and has no unresolved invoice lifecycle.',
                'success',
            );
        }

        if ($quotation) {
            if ($quotation->status === 'Draft') {
                return $this->action(
                    'finish-quotation',
                    'Finish and approve quotation',
                    'Review services, totals, tax, and approval.',
                    'warning',
                );
            }

            if ($quotation->status === 'Approved') {
                return $this->action(
                    'send-quotation',
                    'Send quotation',
                    'Deliver the approved quotation and track the attempt.',
                    'primary',
                );
            }

            return $this->action(
                'create-invoice',
                'Create invoice',
                'Create the single invoice for the approved quotation.',
                'primary',
            );
        }

        if ($meeting) {
            if (
                in_array(
                    $meeting->status,
                    ['Scheduled', 'Confirmed', 'Rescheduled'],
                    true,
                )
            ) {
                return $this->action(
                    'complete-meeting',
                    'Complete the meeting',
                    'Record the meeting outcome to unlock the correct next step.',
                    'warning',
                );
            }

            if (
                $meeting->status === 'Completed'
                && $meeting->outcome === 'Not Interested'
            ) {
                return $this->action(
                    'closed-lost',
                    'Lead closed as lost',
                    'The completed meeting was marked Not Interested.',
                    'danger',
                );
            }

            if ($meeting->status === 'Completed') {
                return $this->action(
                    'create-quotation',
                    'Create a quotation',
                    'Build the quotation from the completed meeting.',
                    'primary',
                );
            }
        }

        if ($lead->lead_status === 'New') {
            return $this->action(
                'mark-contacted',
                'Contact the lead',
                'Record the first contact and move the lead to Contacted.',
                'primary',
            );
        }

        if ($lead->lead_status === 'Contacted') {
            return $this->action(
                'qualify-lead',
                'Qualify the lead',
                'Confirm interest and requirements before scheduling a meeting.',
                'primary',
            );
        }

        return $this->action(
            'schedule-meeting',
            'Schedule a meeting',
            'Create the next meeting without leaving the Sales Workspace.',
            'primary',
        );
    }

    /**
     * @return array{
     *     quotation_total: float,
     *     invoiced_total: float,
     *     credited_total: float,
     *     refunded_total: float,
     *     paid_total: float,
     *     balance_total: float,
     *     meetings: int,
     *     quotations: int,
     *     invoices: int,
     *     payments: int
     * }
     */
    public function summary(Lead $lead): array
    {
        $quotationTotal = round(
            (float) $lead->quotations->sum(
                fn (Quotation $quotation): float =>
                    (float) $quotation->grand_total,
            ),
            2,
        );

        $activeInvoices = $lead->invoices
            ->reject(
                fn (Invoice $invoice): bool =>
                    $invoice->status === 'Void',
            );

        if ($activeInvoices->isNotEmpty()) {
            $invoicedTotal = round(
                (float) $activeInvoices->sum(
                    fn (Invoice $invoice): float =>
                        (float) $invoice->net_total,
                ),
                2,
            );

            $creditedTotal = round(
                (float) $activeInvoices->sum(
                    fn (Invoice $invoice): float =>
                        (float) $invoice->credited_total,
                ),
                2,
            );

            $refundedTotal = round(
                (float) $activeInvoices->sum(
                    fn (Invoice $invoice): float =>
                        (float) $invoice->refunded_total,
                ),
                2,
            );

            $paidTotal = round(
                (float) $activeInvoices->sum(
                    fn (Invoice $invoice): float =>
                        (float) $invoice->total_paid,
                ),
                2,
            );

            $balanceTotal = round(
                (float) $activeInvoices->sum(
                    fn (Invoice $invoice): float =>
                        max((float) $invoice->balance_due, 0),
                ),
                2,
            );
        } else {
            $invoicedTotal = 0.0;
            $creditedTotal = 0.0;
            $refundedTotal = 0.0;
            $paidTotal = $this->paidAmount($lead);
            $balanceTotal = round(
                max($quotationTotal - $paidTotal, 0),
                2,
            );
        }

        return [
            'quotation_total' => $quotationTotal,
            'invoiced_total' => $invoicedTotal,
            'credited_total' => $creditedTotal,
            'refunded_total' => $refundedTotal,
            'paid_total' => round($paidTotal, 2),
            'balance_total' => $balanceTotal,
            'meetings' => $lead->meetings->count(),
            'quotations' => $lead->quotations->count(),
            'invoices' => $lead->invoices->count(),
            'payments' => $this->payments($lead)->count(),
        ];
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     detail: string,
     *     at: CarbonInterface|null,
     *     type: string
     * }>
     */
    public function timeline(Lead $lead): array
    {
        $lead->loadMissing([
            'meetings',
            'quotations.payments',
            'invoices.creditNotes',
            'invoices.refunds',
        ]);

        $events = collect();

        $events->push([
            'title' => 'Lead created',
            'detail' => sprintf(
                '%s entered the sales pipeline.',
                $lead->company_name ?: $lead->contact_person,
            ),
            'at' => $lead->created_at,
            'type' => 'lead',
        ]);

        foreach ($lead->meetings as $meeting) {
            $events->push([
                'title' => sprintf(
                    'Meeting %s',
                    strtolower((string) $meeting->status),
                ),
                'detail' => sprintf(
                    '%s ' . "\u{00B7}" . ' %s',
                    $meeting->meeting_title,
                    $meeting->outcome,
                ),
                'at' => $meeting->updated_at
                    ?: $meeting->created_at,
                'type' => 'meeting',
            ]);
        }

        foreach ($lead->quotations as $quotation) {
            $events->push([
                'title' => sprintf(
                    'Quotation %s',
                    strtolower((string) $quotation->status),
                ),
                'detail' => sprintf(
                    '%s ' . "\u{00B7}" . ' '
                    . "\u{20B9}" . '%s',
                    $quotation->quotation_code,
                    number_format(
                        (float) $quotation->grand_total,
                        2,
                    ),
                ),
                'at' => $quotation->updated_at
                    ?: $quotation->created_at,
                'type' => 'quotation',
            ]);

            foreach ($quotation->payments as $payment) {
                $events->push([
                    'title' => 'Payment received',
                    'detail' => sprintf(
                        '%s ' . "\u{00B7}" . ' '
                        . "\u{20B9}" . '%s',
                        $payment->payment_no,
                        number_format(
                            (float) $payment->amount,
                            2,
                        ),
                    ),
                    'at' => $payment->created_at
                        ?: $payment->payment_date,
                    'type' => 'payment',
                ]);
            }
        }

        foreach ($lead->invoices as $invoice) {
            $events->push([
                'title' => sprintf(
                    'Invoice %s',
                    strtolower((string) $invoice->status),
                ),
                'detail' => sprintf(
                    '%s ' . "\u{00B7}"
                    . ' Balance ' . "\u{20B9}" . '%s',
                    $invoice->invoice_no,
                    number_format(
                        (float) $invoice->balance_due,
                        2,
                    ),
                ),
                'at' => $invoice->issued_at
                    ?: $invoice->updated_at
                    ?: $invoice->created_at,
                'type' => 'invoice',
            ]);

            if ($invoice->email_sent_at) {
                $events->push([
                    'title' => 'Invoice sent',
                    'detail' => sprintf(
                        '%s ' . "\u{00B7}" . ' %s',
                        $invoice->invoice_no,
                        $invoice->last_sent_to
                            ?: 'Recipient unavailable',
                    ),
                    'at' => $invoice->email_sent_at,
                    'type' => 'invoice_delivery',
                ]);
            }

            foreach ($invoice->creditNotes as $creditNote) {
                $events->push([
                    'title' => sprintf(
                        'Credit note %s',
                        strtolower(
                            (string) $creditNote->status,
                        ),
                    ),
                    'detail' => sprintf(
                        '%s ' . "\u{00B7}" . ' '
                        . "\u{20B9}" . '%s',
                        $creditNote->credit_note_no,
                        number_format(
                            (float) $creditNote->grand_total,
                            2,
                        ),
                    ),
                    'at' => $creditNote->issued_at
                        ?: $creditNote->created_at,
                    'type' => 'credit_note',
                ]);
            }

            foreach ($invoice->refunds as $refund) {
                $events->push([
                    'title' => sprintf(
                        'Refund %s',
                        strtolower((string) $refund->status),
                    ),
                    'detail' => sprintf(
                        '%s ' . "\u{00B7}" . ' '
                        . "\u{20B9}" . '%s',
                        $refund->refund_no,
                        number_format(
                            (float) $refund->amount,
                            2,
                        ),
                    ),
                    'at' => $refund->processed_at
                        ?: $refund->created_at,
                    'type' => 'refund',
                ]);
            }
        }

        return $events
            ->sortByDesc(
                fn (array $event): int =>
                    $event['at']?->getTimestamp() ?? 0,
            )
            ->values()
            ->all();
    }
    public function latestMeeting(Lead $lead): ?Meeting
    {
        return $lead->meetings
            ->sortByDesc('id')
            ->first();
    }

    public function latestQuotation(Lead $lead): ?Quotation
    {
        return $lead->quotations
            ->sortByDesc('id')
            ->first();
    }

    public function latestInvoice(Lead $lead): ?Invoice
    {
        return $lead->invoices
            ->sortByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function payments(Lead $lead): Collection
    {
        return $lead->quotations
            ->flatMap(
                fn (Quotation $quotation): Collection =>
                    $quotation->payments,
            )
            ->unique('id')
            ->values();
    }

    private function paidAmount(Lead $lead): float
    {
        return round(
            (float) $this->payments($lead)->sum(
                fn (Payment $payment): float =>
                    (float) $payment->amount,
            ),
            2,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     state: string,
     *     detail: string
     * }
     */
    private function stage(
        string $key,
        string $label,
        string $state,
        string $detail,
    ): array {
        return compact('key', 'label', 'state', 'detail');
    }

    private function state(
        bool $started,
        bool $completed,
        bool $current,
    ): string {
        if ($completed) {
            return 'completed';
        }

        if ($current || $started) {
            return 'current';
        }

        return 'pending';
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     tone: string
     * }
     */
    private function action(
        string $key,
        string $label,
        string $description,
        string $tone,
    ): array {
        return compact('key', 'label', 'description', 'tone');
    }
}
