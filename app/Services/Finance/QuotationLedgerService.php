<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationLedgerService
{
    /**
     * Recalculate the stored payment ledger from active payment records.
     *
     * Soft-deleted payments are intentionally excluded by the standard
     * payments relationship. This makes create, edit, delete, restore, and
     * force-delete operations converge on the same source of truth.
     */
    public function recalculate(Quotation|int|null $quotation): ?Quotation
    {
        $quotationId = $quotation instanceof Quotation
            ? $quotation->getKey()
            : $quotation;

        if (! $quotationId) {
            return null;
        }

        return DB::transaction(function () use ($quotationId): ?Quotation {
            $quotation = Quotation::query()
                ->withTrashed()
                ->lockForUpdate()
                ->find($quotationId);

            if (! $quotation) {
                return null;
            }

            $grandTotal = round(max((float) $quotation->grand_total, 0), 2);
            $totalPaid = round((float) $quotation->payments()->sum('amount'), 2);
            $balanceDue = round(max($grandTotal - $totalPaid, 0), 2);

            $paymentStatus = match (true) {
                $totalPaid <= 0 => 'Unpaid',
                $balanceDue > 0 => 'Partially Paid',
                default => 'Paid',
            };

            $quotation->forceFill([
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
            ])->saveQuietly();

            return $quotation->refresh();
        }, attempts: 3);
    }

    /**
     * @param iterable<int|null> $quotationIds
     */
    public function recalculateMany(iterable $quotationIds): void
    {
        $ids = collect($quotationIds)
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        foreach ($ids as $quotationId) {
            $this->recalculate($quotationId);
        }
    }
}
