<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\Documents\CreditNoteDocumentService;
use App\Services\Documents\CreditNoteDocumentStorage;
use App\Services\Documents\InvoiceDocumentRefreshService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreditNoteService
{
    public function __construct(
        private readonly InvoiceLedgerService $invoiceLedgers,
        private readonly SalesCompletionService $salesCompletion,
        private readonly CreditNoteDocumentService $documents,
        private readonly CreditNoteDocumentStorage $documentStorage,
        private readonly InvoiceDocumentRefreshService $invoiceDocuments,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Invoice $invoice, array $data): CreditNote
    {
        $validated = Validator::make($data, [
            'amount' => ['required', 'numeric', 'gt:0'],
            'tax' => ['sometimes', 'numeric', 'gte:0'],
            'reason' => ['required', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['sometimes', 'date'],
        ])->validate();

        return DB::transaction(function () use (
            $invoice,
            $validated,
        ): CreditNote {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            $this->assertAdjustable($lockedInvoice);

            $amount = round((float) $validated['amount'], 2);
            $tax = round((float) ($validated['tax'] ?? 0), 2);

            if ($tax > $amount) {
                throw ValidationException::withMessages([
                    'tax' => 'Credit-note tax cannot exceed its total amount.',
                ]);
            }

            $this->assertWithinRemainingCredit($lockedInvoice, $amount);

            $creditNote = CreditNote::query()->create([
                'credit_note_no' => CreditNote::nextCreditNoteNumber(),
                'invoice_id' => $lockedInvoice->getKey(),
                'customer_id' => $lockedInvoice->customer_id,
                'issue_date' => isset($validated['issue_date'])
                    ? Carbon::parse($validated['issue_date'])->toDateString()
                    : now()->toDateString(),
                'status' => 'Draft',
                'currency' => $lockedInvoice->currency,
                'reason' => trim((string) $validated['reason']),
                'subtotal' => round($amount - $tax, 2),
                'tax' => $tax,
                'grand_total' => $amount,
                'is_active' => true,
            ]);

            $creditNote->items()->create([
                'description' => trim((string) (
                    $validated['description']
                    ?? $validated['reason']
                )),
                'quantity' => 1,
                'unit_price' => round($amount - $tax, 2),
                'tax' => $tax,
                'line_total' => $amount,
                'sort_order' => 1,
            ]);

            return $creditNote->refresh()->load([
                'invoice',
                'customer',
                'items',
            ]);
        }, attempts: 3);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAndIssue(
        Invoice $invoice,
        array $data,
    ): CreditNote {
        return DB::transaction(
            fn (): CreditNote => $this->issue(
                $this->create($invoice, $data),
            ),
            attempts: 3,
        );
    }

    public function issue(CreditNote $creditNote): CreditNote
    {
        $generatedPath = null;
        $invoiceDocumentSnapshot = null;
        $generatedInvoicePath = null;

        try {
            return DB::transaction(function () use (
                $creditNote,
                &$generatedPath,
                &$invoiceDocumentSnapshot,
                &$generatedInvoicePath,
            ): CreditNote {
                $lockedCreditNote = CreditNote::query()
                    ->with(['invoice', 'customer', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($creditNote->getKey());

                $lockedInvoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedCreditNote->invoice_id);

                $invoiceDocumentSnapshot = $this->invoiceDocuments
                    ->snapshot($lockedInvoice);

                if ($lockedCreditNote->status !== 'Draft') {
                    throw ValidationException::withMessages([
                        'status' => 'Only draft credit notes can be issued.',
                    ]);
                }

                $this->assertAdjustable($lockedInvoice);
                $this->assertWithinRemainingCredit(
                    $lockedInvoice,
                    (float) $lockedCreditNote->grand_total,
                    $lockedCreditNote->getKey(),
                );

                $lockedCreditNote->forceFill([
                    'status' => 'Issued',
                    'issued_at' => now(),
                    'issued_by' => auth()->id(),
                    'credit_note_uuid' => $lockedCreditNote->credit_note_uuid
                        ?: (string) Str::uuid(),
                    'verification_hash' => $lockedCreditNote->verification_hash
                        ?: CreditNote::generateVerificationHash(),
                ])->save();

                $generatedPath = $this->documents->generate(
                    $lockedCreditNote,
                );

                $lockedCreditNote->forceFill([
                    'credit_note_pdf' => $generatedPath,
                    'credit_note_generated_at' => now(),
                ])->save();

                $updatedInvoice = $this->invoiceLedgers->recalculate(
                    $lockedInvoice,
                );

                if ($updatedInvoice) {
                    $generatedInvoicePath = $this->invoiceDocuments
                        ->regenerate($updatedInvoice);
                }

                $this->salesCompletion->synchronizeQuotation(
                    $lockedInvoice->quotation_id,
                );

                return $lockedCreditNote->refresh()->load([
                    'invoice',
                    'customer',
                    'items',
                ]);
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->documentStorage->delete(
                $generatedPath ?: $this->expectedDocumentPath($creditNote),
            );
            $this->invoiceDocuments->restore(
                $invoiceDocumentSnapshot,
                $generatedInvoicePath,
            );

            throw $exception;
        }
    }

    public function void(CreditNote $creditNote, string $reason): CreditNote
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'void_reason' => 'A void reason is required.',
            ]);
        }

        $creditNoteDocumentSnapshot = null;
        $generatedCreditNotePath = null;
        $invoiceDocumentSnapshot = null;
        $generatedInvoicePath = null;

        try {
            return DB::transaction(function () use (
                $creditNote,
                $reason,
                &$creditNoteDocumentSnapshot,
                &$generatedCreditNotePath,
                &$invoiceDocumentSnapshot,
                &$generatedInvoicePath,
            ): CreditNote {
                $lockedCreditNote = CreditNote::query()
                    ->lockForUpdate()
                    ->findOrFail($creditNote->getKey());

                if ($lockedCreditNote->status === 'Void') {
                    return $lockedCreditNote;
                }

                if ($lockedCreditNote->status !== 'Issued') {
                    throw ValidationException::withMessages([
                        'status' => 'Only issued credit notes can be voided.',
                    ]);
                }

                if ($lockedCreditNote->refunds()
                    ->where('status', 'Processed')
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'status' =>
                            'A credit note linked to a processed refund cannot be voided.',
                    ]);
                }

                $invoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedCreditNote->invoice_id);
                $creditNoteDocumentSnapshot = $this->documentStorage
                    ->snapshot($lockedCreditNote->credit_note_pdf);
                $invoiceDocumentSnapshot = $this->invoiceDocuments
                    ->snapshot($invoice);

                $lockedCreditNote->forceFill([
                    'status' => 'Void',
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'void_reason' => $reason,
                ])->save();

                $generatedCreditNotePath = $this->documents->generate(
                    $lockedCreditNote->refresh(),
                );
                $lockedCreditNote->forceFill([
                    'credit_note_pdf' => $generatedCreditNotePath,
                    'credit_note_generated_at' => now(),
                ])->save();

                $updatedInvoice = $this->invoiceLedgers->recalculate($invoice);

                if ($updatedInvoice) {
                    $generatedInvoicePath = $this->invoiceDocuments
                        ->regenerate($updatedInvoice);
                }

                $this->salesCompletion->synchronizeQuotation(
                    $updatedInvoice?->quotation_id,
                );

                return $lockedCreditNote->refresh();
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->documentStorage->restore(
                $creditNoteDocumentSnapshot,
                $generatedCreditNotePath,
            );
            $this->invoiceDocuments->restore(
                $invoiceDocumentSnapshot,
                $generatedInvoicePath,
            );

            throw $exception;
        }
    }

    private function assertAdjustable(Invoice $invoice): void
    {
        if (! $invoice->issued_at || $invoice->status === 'Draft') {
            throw ValidationException::withMessages([
                'invoice_id' => 'Only issued invoices can receive credit notes.',
            ]);
        }

        if ($invoice->status === 'Void' || $invoice->trashed()) {
            throw ValidationException::withMessages([
                'invoice_id' => 'The invoice is unavailable for adjustment.',
            ]);
        }
    }

    private function assertWithinRemainingCredit(
        Invoice $invoice,
        float $amount,
        ?int $excludingCreditNoteId = null,
    ): void {
        $query = $invoice->creditNotes()
            ->where('status', 'Issued');

        if ($excludingCreditNoteId) {
            $query->where('id', '!=', $excludingCreditNoteId);
        }

        $issuedTotal = round((float) $query->sum('grand_total'), 2);
        $remaining = round(max(
            (float) $invoice->grand_total - $issuedTotal,
            0,
        ), 2);

        if (round($amount, 2) > $remaining) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Credit amount cannot exceed the remaining invoice value of ₹ %s.',
                    number_format($remaining, 2),
                ),
            ]);
        }
    }

    private function expectedDocumentPath(
        CreditNote $creditNote,
    ): ?string {
        return filled($creditNote->credit_note_no)
            ? 'credit-notes/' . $creditNote->credit_note_no . '.pdf'
            : null;
    }
}
