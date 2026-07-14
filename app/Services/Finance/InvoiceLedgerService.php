<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class InvoiceLedgerService
{
    public function recalculate(Invoice|int|null $invoice): ?Invoice
    {
        $invoiceId = $invoice instanceof Invoice
            ? $invoice->getKey()
            : $invoice;

        if (! $invoiceId) {
            return null;
        }

        return DB::transaction(function () use ($invoiceId): ?Invoice {
            $invoice = Invoice::query()
                ->withTrashed()
                ->lockForUpdate()
                ->find($invoiceId);

            if (! $invoice) {
                return null;
            }

            $grandTotal = round(max((float) $invoice->grand_total, 0), 2);
            $totalPaid = round((float) $invoice->allocations()
                ->whereHas('payment')
                ->sum('amount'), 2);
            $balanceDue = round(max($grandTotal - $totalPaid, 0), 2);

            $status = $this->statusFor(
                $invoice,
                $totalPaid,
                $balanceDue,
            );

            $invoice->forceFill([
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'status' => $status,
            ])->saveQuietly();

            return $invoice->refresh();
        }, attempts: 3);
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
        float $totalPaid,
        float $balanceDue,
    ): string {
        if ($invoice->status === 'Void') {
            return 'Void';
        }

        if (! $invoice->issued_at) {
            return 'Draft';
        }

        if ($balanceDue <= 0 && $totalPaid > 0) {
            return 'Paid';
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
