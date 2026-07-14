<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Invoice;

class InvoiceDocumentRefreshService
{
    public function __construct(
        private readonly InvoiceDocumentService $documents,
        private readonly InvoiceDocumentStorage $storage,
    ) {
    }

    /**
     * @return array{path: ?string, existed: bool, contents: ?string}
     */
    public function snapshot(Invoice $invoice): array
    {
        return $this->storage->snapshot($invoice->invoice_pdf);
    }

    public function regenerate(Invoice $invoice): ?string
    {
        if (! $invoice->issued_at || $invoice->status === 'Void') {
            return null;
        }

        $path = $this->documents->generate($invoice);

        $invoice->forceFill([
            'invoice_pdf' => $path,
            'invoice_generated_at' => now(),
        ])->saveQuietly();

        return $path;
    }

    /**
     * @param array{path: ?string, existed: bool, contents: ?string}|null $snapshot
     */
    public function restore(?array $snapshot, ?string $generatedPath): void
    {
        $this->storage->restore($snapshot, $generatedPath);
    }
}
