<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

class PaymentAllocationService
{
    public function __construct(
        private readonly InvoiceLedgerService $invoiceLedgers,
    ) {
    }

    /**
     * Synchronize one payment to the single invoice associated with its
     * quotation. Existing allocations are moved atomically when the payment
     * moves to another quotation.
     *
     * @return array<int, int> affected invoice IDs
     */
    public function sync(Payment $payment): array
    {
        return DB::transaction(function () use ($payment): array {
            $lockedPayment = Payment::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            $currentInvoiceIds = $this->invoiceIdsForPayment(
                $lockedPayment,
            );

            $targetInvoice = null;

            if (! $lockedPayment->trashed()) {
                $targetInvoice = Invoice::query()
                    ->where('quotation_id', $lockedPayment->quotation_id)
                    ->where('status', '!=', 'Void')
                    ->lockForUpdate()
                    ->first();
            }

            $targetInvoiceId = $targetInvoice?->getKey();

            $lockedPayment->allocations()
                ->when(
                    $targetInvoiceId,
                    fn ($query) => $query->where(
                        'invoice_id',
                        '!=',
                        $targetInvoiceId,
                    ),
                )
                ->when(
                    ! $targetInvoiceId,
                    fn ($query) => $query,
                )
                ->delete();

            if ($targetInvoiceId) {
                PaymentAllocation::query()->updateOrCreate(
                    [
                        'payment_id' => $lockedPayment->getKey(),
                        'invoice_id' => $targetInvoiceId,
                    ],
                    [
                        'amount' => $lockedPayment->amount,
                    ],
                );
            }

            $affectedInvoiceIds = collect($currentInvoiceIds)
                ->push($targetInvoiceId)
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->invoiceLedgers->recalculateMany(
                $affectedInvoiceIds,
            );

            return $affectedInvoiceIds;
        }, attempts: 3);
    }

    /**
     * Allocate all active quotation payments when an invoice is created.
     *
     * @return array<int, int>
     */
    public function allocateExistingPayments(Invoice $invoice): array
    {
        return DB::transaction(function () use ($invoice): array {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            $payments = Payment::query()
                ->where('quotation_id', $lockedInvoice->quotation_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                PaymentAllocation::query()->updateOrCreate(
                    [
                        'payment_id' => $payment->getKey(),
                        'invoice_id' => $lockedInvoice->getKey(),
                    ],
                    [
                        'amount' => $payment->amount,
                    ],
                );
            }

            $this->invoiceLedgers->recalculate($lockedInvoice);

            return [$lockedInvoice->getKey()];
        }, attempts: 3);
    }

    /**
     * @return array<int, int>
     */
    public function invoiceIdsForPayment(Payment $payment): array
    {
        return $payment->allocations()
            ->pluck('invoice_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
