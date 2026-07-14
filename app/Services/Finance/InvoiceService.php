<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\InvoiceDocumentStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceLedgerService $invoiceLedgers,
        private readonly PaymentAllocationService $allocations,
        private readonly SalesCompletionService $salesCompletion,
        private readonly InvoiceDocumentService $documents,
        private readonly InvoiceDocumentStorage $documentStorage,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createFromQuotation(
        Quotation $quotation,
        array $data = [],
    ): Invoice {
        $validated = Validator::make($data, [
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'customer_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use (
            $quotation,
            $validated,
        ): Invoice {
            $lockedQuotation = Quotation::query()
                ->with(['items', 'customer', 'lead'])
                ->lockForUpdate()
                ->findOrFail($quotation->getKey());

            if (! in_array(
                $lockedQuotation->status,
                ['Approved', 'Sent', 'Accepted', 'Completed'],
                true,
            )) {
                throw ValidationException::withMessages([
                    'quotation_id' =>
                        'Only approved or later quotations can be invoiced.',
                ]);
            }

            if (! $lockedQuotation->customer_id) {
                throw ValidationException::withMessages([
                    'customer_id' =>
                        'The quotation must have a customer before invoicing.',
                ]);
            }

            if ((float) $lockedQuotation->grand_total <= 0) {
                throw ValidationException::withMessages([
                    'grand_total' =>
                        'The quotation must have a positive total before invoicing.',
                ]);
            }

            $existingInvoice = Invoice::query()
                ->withTrashed()
                ->where('quotation_id', $lockedQuotation->getKey())
                ->lockForUpdate()
                ->first();

            if ($existingInvoice) {
                throw ValidationException::withMessages([
                    'quotation_id' =>
                        'This quotation already has an invoice.',
                ]);
            }

            $invoiceDate = isset($validated['invoice_date'])
                ? Carbon::parse($validated['invoice_date'])->toDateString()
                : now()->toDateString();
            $dueDate = isset($validated['due_date'])
                ? Carbon::parse($validated['due_date'])->toDateString()
                : now()->addDays(7)->toDateString();

            if ($dueDate < $invoiceDate) {
                throw ValidationException::withMessages([
                    'due_date' =>
                        'The due date cannot be before the invoice date.',
                ]);
            }

            $invoice = Invoice::query()->create([
                'invoice_no' => Invoice::nextInvoiceNumber(),
                'quotation_id' => $lockedQuotation->getKey(),
                'customer_id' => $lockedQuotation->customer_id,
                'lead_id' => $lockedQuotation->lead_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => 'Draft',
                'currency' => $lockedQuotation->customer?->currency ?: 'INR',
                'subtotal' => $lockedQuotation->subtotal,
                'discount_type' => $lockedQuotation->discount_type,
                'discount_value' => $lockedQuotation->discount_value,
                'tax' => $lockedQuotation->tax,
                'grand_total' => $lockedQuotation->grand_total,
                'credited_total' => 0,
                'refunded_total' => 0,
                'net_total' => $lockedQuotation->grand_total,
                'total_paid' => 0,
                'balance_due' => $lockedQuotation->grand_total,
                'customer_notes' => $validated['customer_notes']
                    ?? $lockedQuotation->customer_notes,
                'internal_notes' => $validated['internal_notes']
                    ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($lockedQuotation->items as $item) {
                $invoice->items()->create([
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'line_total' => $item->line_total,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $this->allocations->allocateExistingPayments($invoice);

            return $invoice->refresh()->load([
                'quotation',
                'customer',
                'lead',
                'items',
            ]);
        }, attempts: 3);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $validated = Validator::make($data, [
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'customer_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use (
            $invoice,
            $validated,
        ): Invoice {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            if ($lockedInvoice->status !== 'Draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft invoices can be edited.',
                ]);
            }

            $payload = Arr::only($validated, [
                'invoice_date',
                'due_date',
                'customer_notes',
                'internal_notes',
                'is_active',
            ]);

            $invoiceDate = isset($payload['invoice_date'])
                ? Carbon::parse($payload['invoice_date'])->toDateString()
                : $lockedInvoice->invoice_date?->toDateString();
            $dueDate = isset($payload['due_date'])
                ? Carbon::parse($payload['due_date'])->toDateString()
                : $lockedInvoice->due_date?->toDateString();

            if (isset($payload['invoice_date'])) {
                $payload['invoice_date'] = $invoiceDate;
            }

            if (isset($payload['due_date'])) {
                $payload['due_date'] = $dueDate;
            }

            if ($invoiceDate && $dueDate && $dueDate < $invoiceDate) {
                throw ValidationException::withMessages([
                    'due_date' =>
                        'The due date cannot be before the invoice date.',
                ]);
            }

            $lockedInvoice->update($payload);

            return $lockedInvoice->refresh();
        }, attempts: 3);
    }

    public function issue(Invoice $invoice): Invoice
    {
        $generatedPath = null;
        $invoiceId = $invoice->getKey();
        $quotationId = $invoice->quotation_id;

        try {
            return DB::transaction(function () use (
                $invoiceId,
                $quotationId,
                &$generatedPath,
            ): Invoice {
                Quotation::query()
                    ->lockForUpdate()
                    ->findOrFail($quotationId);

                $lockedInvoice = Invoice::query()
                    ->with(['quotation', 'customer', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($invoiceId);

                if ($lockedInvoice->status !== 'Draft') {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only draft invoices can be issued.',
                    ]);
                }

                $this->assertSnapshotMatchesQuotation($lockedInvoice);

                $lockedInvoice->forceFill([
                    'status' => 'Issued',
                    'issued_at' => now(),
                    'issued_by' => auth()->id(),
                    'invoice_uuid' => $lockedInvoice->invoice_uuid
                        ?: (string) Str::uuid(),
                    'verification_hash' => $lockedInvoice->verification_hash
                        ?: Invoice::generateVerificationHash(),
                ])->save();

                $lockedInvoice = $this->invoiceLedgers
                    ->recalculate($lockedInvoice)
                    ?? $lockedInvoice->refresh();

                $generatedPath = $this->documents
                    ->generate($lockedInvoice);

                $lockedInvoice->forceFill([
                    'invoice_pdf' => $generatedPath,
                    'invoice_generated_at' => now(),
                ])->save();

                $this->salesCompletion->synchronizeQuotation(
                    $lockedInvoice->quotation_id,
                );

                return $lockedInvoice->refresh()->load([
                    'quotation',
                    'customer',
                    'lead',
                    'items',
                ]);
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->documentStorage->delete(
                $generatedPath ?: $this->expectedDocumentPath($invoice),
            );

            throw $exception;
        }
    }

    public function void(Invoice $invoice, string $reason): Invoice
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'void_reason' => 'A void reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $invoice,
            $reason,
        ): Invoice {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            if ($lockedInvoice->status === 'Void') {
                return $lockedInvoice;
            }

            if ((float) $lockedInvoice->total_paid > 0) {
                throw ValidationException::withMessages([
                    'status' =>
                        'An invoice with allocated payments cannot be voided.',
                ]);
            }

            if ($lockedInvoice->creditNotes()
                ->where('status', 'Issued')
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Void issued credit notes before voiding the invoice.',
                ]);
            }

            if ($lockedInvoice->refunds()
                ->where('status', 'Processed')
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'An invoice with processed refunds cannot be voided.',
                ]);
            }

            $lockedInvoice->forceFill([
                'status' => 'Void',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $reason,
            ])->save();

            $this->invoiceLedgers->recalculate($lockedInvoice);
            $this->salesCompletion->synchronizeQuotation(
                $lockedInvoice->quotation_id,
            );

            return $lockedInvoice->refresh();
        }, attempts: 3);
    }

    private function assertSnapshotMatchesQuotation(
        Invoice $invoice,
    ): void {
        $quotation = $invoice->quotation;

        if (
            ! $quotation
            || (int) $invoice->customer_id !== (int) $quotation->customer_id
            || round((float) $invoice->grand_total, 2)
                !== round((float) $quotation->grand_total, 2)
        ) {
            throw ValidationException::withMessages([
                'quotation_id' =>
                    'The invoice snapshot no longer matches its quotation.',
            ]);
        }
    }

    private function expectedDocumentPath(Invoice $invoice): ?string
    {
        return filled($invoice->invoice_no)
            ? 'invoices/' . $invoice->invoice_no . '.pdf'
            : null;
    }
}
