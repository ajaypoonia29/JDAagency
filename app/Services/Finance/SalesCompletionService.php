<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\CRM\LeadStatusService;
use Illuminate\Support\Facades\DB;

class SalesCompletionService
{
    public function __construct(
        private readonly LeadStatusService $leadStatuses,
    ) {
    }

    public function synchronizeQuotation(
        Quotation|int|null $quotation,
    ): ?Quotation {
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

            if (! $quotation || $quotation->trashed()) {
                return $quotation;
            }

            $invoice = Invoice::query()
                ->where('quotation_id', $quotation->getKey())
                ->lockForUpdate()
                ->first();

            $isComplete = $invoice
                ? $invoice->status === 'Paid'
                : $quotation->payment_status === 'Paid';

            if (! $isComplete) {
                return $quotation->refresh();
            }

            if ($quotation->status !== 'Completed') {
                $quotation->forceFill([
                    'status' => 'Completed',
                ])->saveQuietly();
            }

            if ($quotation->lead_id) {
                $lead = Lead::query()
                    ->lockForUpdate()
                    ->find($quotation->lead_id);

                if ($lead) {
                    $this->leadStatuses->advanceLocked($lead, 'Won');
                }
            }

            return $quotation->refresh();
        }, attempts: 3);
    }

    /**
     * @param iterable<int|string|null> $quotationIds
     */
    public function synchronizeMany(iterable $quotationIds): void
    {
        collect($quotationIds)
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->each(fn (int $id) => $this->synchronizeQuotation($id));
    }
}
