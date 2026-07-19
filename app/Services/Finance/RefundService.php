<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Documents\InvoiceDocumentRefreshService;
use App\Services\Documents\RefundDocumentService;
use App\Services\Documents\RefundDocumentStorage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RefundService
{
    public function __construct(
        private readonly InvoiceLedgerService $invoiceLedgers,
        private readonly SalesCompletionService $salesCompletion,
        private readonly RefundDocumentService $documents,
        private readonly RefundDocumentStorage $documentStorage,
        private readonly InvoiceDocumentRefreshService $invoiceDocuments,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function process(Invoice $invoice, array $data): Refund
    {
        $validated = Validator::make($data, [
            'payment_id' => ['required', 'integer'],
            'credit_note_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'refund_method' => [
                'required',
                'string',
                'in:Cash,UPI,Bank Transfer,Cheque,Credit Card,Debit Card,Original Method',
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'refund_date' => ['sometimes', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ])->validate();

        $generatedPath = null;
        $refundNumber = null;
        $invoiceDocumentSnapshot = null;
        $generatedInvoicePath = null;

        try {
            return DB::transaction(function () use (
                $invoice,
                $validated,
                &$generatedPath,
                &$refundNumber,
                &$invoiceDocumentSnapshot,
                &$generatedInvoicePath,
            ): Refund {
                $lockedInvoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($invoice->getKey());

                $this->assertRefundableInvoice($lockedInvoice);
                $invoiceDocumentSnapshot = $this->invoiceDocuments
                    ->snapshot($lockedInvoice);

                $payment = Payment::query()
                    ->lockForUpdate()
                    ->find($validated['payment_id']);

                if (
                    ! $payment
                    || $payment->quotation_id !== $lockedInvoice->quotation_id
                    || ! $payment->allocations()
                        ->where('invoice_id', $lockedInvoice->getKey())
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'payment_id' =>
                            'The selected payment is not allocated to this invoice.',
                    ]);
                }

                $creditNote = null;

                if (filled($validated['credit_note_id'] ?? null)) {
                    $creditNote = CreditNote::query()
                        ->lockForUpdate()
                        ->find($validated['credit_note_id']);

                    if (
                        ! $creditNote
                        || $creditNote->invoice_id !== $lockedInvoice->getKey()
                        || $creditNote->status !== 'Issued'
                    ) {
                        throw ValidationException::withMessages([
                            'credit_note_id' =>
                                'The selected credit note is not an issued note for this invoice.',
                        ]);
                    }
                }

                $amount = round((float) $validated['amount'], 2);
                $this->assertWithinRefundableBalances(
                    $lockedInvoice,
                    $payment,
                    $creditNote,
                    $amount,
                );

                $refundNumber = Refund::nextRefundNumber();

                $refund = Refund::query()->create([
                    'refund_no' => $refundNumber,
                    'invoice_id' => $lockedInvoice->getKey(),
                    'payment_id' => $payment->getKey(),
                    'credit_note_id' => $creditNote?->getKey(),
                    'customer_id' => $lockedInvoice->customer_id,
                    'amount' => $amount,
                    'refund_method' => $validated['refund_method'],
                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,
                    'refund_date' => isset($validated['refund_date'])
                        ? Carbon::parse($validated['refund_date'])->toDateString()
                        : now()->toDateString(),
                    'reason' => trim((string) $validated['reason']),
                    'status' => 'Processed',
                    'processed_at' => now(),
                    'processed_by' => auth()->id(),
                    'refund_uuid' => (string) Str::uuid(),
                    'verification_hash' => Refund::generateVerificationHash(),
                    'is_active' => true,
                ]);

                $generatedPath = $this->documents->generate($refund);

                $refund->forceFill([
                    'refund_pdf' => $generatedPath,
                    'refund_generated_at' => now(),
                ])->save();

                $updatedInvoice = $this->invoiceLedgers->recalculate(
                    $lockedInvoice,
                );

                if ($updatedInvoice) {
                    $generatedInvoicePath = $this->invoiceDocuments
                        ->regenerate($updatedInvoice);
                }

                $this->salesCompletion->synchronizeQuotation(
                    $updatedInvoice?->quotation_id,
                );

                return $refund->refresh()->load([
                    'invoice',
                    'payment',
                    'creditNote',
                    'customer',
                ]);
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->documentStorage->delete(
                $generatedPath
                ?: ($refundNumber ? 'refunds/' . $refundNumber . '.pdf' : null),
            );
            $this->invoiceDocuments->restore(
                $invoiceDocumentSnapshot,
                $generatedInvoicePath,
            );

            throw $exception;
        }
    }

    public function cancel(Refund $refund, string $reason): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'cancel_reason' => 'A cancellation reason is required.',
            ]);
        }

        $refundDocumentSnapshot = null;
        $generatedRefundPath = null;
        $invoiceDocumentSnapshot = null;
        $generatedInvoicePath = null;

        try {
            return DB::transaction(function () use (
                $refund,
                $reason,
                &$refundDocumentSnapshot,
                &$generatedRefundPath,
                &$invoiceDocumentSnapshot,
                &$generatedInvoicePath,
            ): Refund {
                $lockedRefund = Refund::query()
                    ->lockForUpdate()
                    ->findOrFail($refund->getKey());

                if ($lockedRefund->status === 'Cancelled') {
                    return $lockedRefund;
                }

                if ($lockedRefund->status !== 'Processed') {
                    throw ValidationException::withMessages([
                        'status' => 'Only processed refunds can be cancelled.',
                    ]);
                }

                $invoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedRefund->invoice_id);
                $refundDocumentSnapshot = $this->documentStorage
                    ->snapshot($lockedRefund->refund_pdf);
                $invoiceDocumentSnapshot = $this->invoiceDocuments
                    ->snapshot($invoice);

                $lockedRefund->forceFill([
                    'status' => 'Cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $reason,
                ])->save();

                $generatedRefundPath = $this->documents->generate(
                    $lockedRefund->refresh(),
                );
                $lockedRefund->forceFill([
                    'refund_pdf' => $generatedRefundPath,
                    'refund_generated_at' => now(),
                ])->save();

                $updatedInvoice = $this->invoiceLedgers->recalculate($invoice);

                if ($updatedInvoice) {
                    $generatedInvoicePath = $this->invoiceDocuments
                        ->regenerate($updatedInvoice);
                }

                $this->salesCompletion->synchronizeQuotation(
                    $updatedInvoice?->quotation_id,
                );

                return $lockedRefund->refresh();
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->documentStorage->restore(
                $refundDocumentSnapshot,
                $generatedRefundPath,
            );
            $this->invoiceDocuments->restore(
                $invoiceDocumentSnapshot,
                $generatedInvoicePath,
            );

            throw $exception;
        }
    }

    private function assertRefundableInvoice(Invoice $invoice): void
    {
        if (! $invoice->issued_at || $invoice->status === 'Draft') {
            throw ValidationException::withMessages([
                'invoice_id' => 'Only issued invoices can be refunded.',
            ]);
        }

        if ($invoice->status === 'Void' || $invoice->trashed()) {
            throw ValidationException::withMessages([
                'invoice_id' => 'The invoice is unavailable for refunds.',
            ]);
        }
    }

    private function assertWithinRefundableBalances(
        Invoice $invoice,
        Payment $payment,
        ?CreditNote $creditNote,
        float $amount,
    ): void {
        $paymentAllocated = round((float) $payment->allocations()
            ->where('invoice_id', $invoice->getKey())
            ->sum('amount'), 2);
        $paymentRefunded = round((float) $payment->refunds()
            ->where('invoice_id', $invoice->getKey())
            ->where('status', 'Processed')
            ->sum('amount'), 2);
        $paymentAvailable = round(max(
            $paymentAllocated - $paymentRefunded,
            0,
        ), 2);

        $grossPaid = round((float) $invoice->allocations()
            ->whereHas('payment')
            ->sum('amount'), 2);
        $invoiceRefunded = round((float) $invoice->refunds()
            ->where('status', 'Processed')
            ->sum('amount'), 2);
        $invoiceAvailable = round(max(
            $grossPaid - $invoiceRefunded,
            0,
        ), 2);

        $available = min($paymentAvailable, $invoiceAvailable);

        if ($creditNote) {
            $creditRefunded = round((float) $creditNote->refunds()
                ->where('status', 'Processed')
                ->sum('amount'), 2);
            $creditAvailable = round(max(
                (float) $creditNote->grand_total - $creditRefunded,
                0,
            ), 2);
            $available = min($available, $creditAvailable);
        }

        if ($amount > $available) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Refund amount cannot exceed the available refundable amount of ₹ %s.',
                    number_format($available, 2),
                ),
            ]);
        }
    }
}
