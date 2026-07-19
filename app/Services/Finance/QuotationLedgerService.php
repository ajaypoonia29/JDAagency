<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationLedgerService
{
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

            $invoice = Invoice::query()
                ->where('quotation_id', $quotation->getKey())
                ->where('status', '!=', 'Void')
                ->lockForUpdate()
                ->first();

            if ($invoice) {
                $effectiveTotal = round(
                    max((float) $invoice->net_total, 0),
                    2,
                );
                $totalPaid = round(
                    max((float) $invoice->total_paid, 0),
                    2,
                );
                $balanceDue = round(
                    max((float) $invoice->balance_due, 0),
                    2,
                );
            } else {
                $effectiveTotal = round(
                    max((float) $quotation->grand_total, 0),
                    2,
                );
                $totalPaid = round(
                    (float) $quotation->payments()->sum('amount'),
                    2,
                );
                $balanceDue = round(
                    max($effectiveTotal - $totalPaid, 0),
                    2,
                );
            }

            $paymentStatus = match (true) {
                $balanceDue <= 0 => 'Paid',
                $totalPaid <= 0 => 'Unpaid',
                default => 'Partially Paid',
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
        collect($quotationIds)
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->each(fn (int $id) => $this->recalculate($id));
    }
}
