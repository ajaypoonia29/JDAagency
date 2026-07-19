<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class InvoiceLedgerService
{
    public function __construct(
        private readonly QuotationLedgerService $quotationLedgers,
    ) {
    }

    public function recalculate(Invoice|int|null $invoice): ?Invoice
    {
        $invoiceId = $invoice instanceof Invoice
            ? $invoice->getKey()
            : $invoice;

        if (! $invoiceId) {
            return null;
        }

        $result = DB::transaction(function () use ($invoiceId): ?Invoice {
            $invoice = Invoice::query()
                ->withTrashed()
                ->lockForUpdate()
                ->find($invoiceId);

            if (! $invoice) {
                return null;
            }

            $grandTotal = round(max((float) $invoice->grand_total, 0), 2);
            $creditedTotal = round((float) $invoice->creditNotes()
                ->where('status', 'Issued')
                ->sum('grand_total'), 2);
            $creditedTotal = min($creditedTotal, $grandTotal);

            $refundedTotal = round((float) $invoice->refunds()
                ->where('status', 'Processed')
                ->sum('amount'), 2);

            $grossPaid = round((float) $invoice->allocations()
                ->whereHas('payment')
                ->sum('amount'), 2);

            $netTotal = round(max($grandTotal - $creditedTotal, 0), 2);
            $totalPaid = round(max($grossPaid - $refundedTotal, 0), 2);
            $balanceDue = round(max($netTotal - $totalPaid, 0), 2);

            $invoice->forceFill([
                'credited_total' => $creditedTotal,
                'refunded_total' => $refundedTotal,
                'net_total' => $netTotal,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'status' => $this->statusFor(
                    $invoice,
                    $netTotal,
                    $totalPaid,
                    $balanceDue,
                    $refundedTotal,
                ),
            ])->saveQuietly();

            return $invoice->refresh();
        }, attempts: 3);

        if ($result) {
            $this->quotationLedgers->recalculate($result->quotation_id);
        }

        return $result;
    }

    /**
     * @param iterable<int|string|null> $invoiceIds
     */
    public function recalculateMany(iterable $invoiceIds): void
    {
        collect($invoiceIds)
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->each(fn (int $id) => $this->recalculate($id));
    }

    /**
     * Refresh date-driven statuses without changing financial records.
     */
    public function refreshOpenInvoices(): int
    {
        $updated = 0;

        Invoice::query()
            ->whereNotNull('issued_at')
            ->whereNotIn('status', ['Draft', 'Void'])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use (&$updated): void {
                foreach ($invoices as $invoice) {
                    $before = $invoice->status;
                    $after = $this->recalculate($invoice)?->status;

                    if ($after !== null && $after !== $before) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * @return array<int, int>
     */
    public function invoiceIdsForPayment(Payment|int $payment): array
    {
        $paymentId = $payment instanceof Payment
            ? $payment->getKey()
            : $payment;

        return DB::table('payment_allocations')
            ->where('payment_id', $paymentId)
            ->pluck('invoice_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function statusFor(
        Invoice $invoice,
        float $netTotal,
        float $totalPaid,
        float $balanceDue,
        float $refundedTotal,
    ): string {
        if ($invoice->status === 'Void') {
            return 'Void';
        }

        if (! $invoice->issued_at) {
            return 'Draft';
        }

        if ($netTotal <= 0) {
            return 'Credited';
        }

        if ($balanceDue <= 0 && $totalPaid > 0) {
            return 'Paid';
        }

        if ($refundedTotal > 0 && $totalPaid <= 0) {
            return 'Refunded';
        }

        if ($invoice->due_date?->isBefore(today())) {
            return 'Overdue';
        }

        if ($totalPaid > 0) {
            return 'Partially Paid';
        }

        return 'Issued';
    }
}
