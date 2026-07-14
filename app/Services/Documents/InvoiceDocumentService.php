<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceDocumentService
{
    public function __construct(
        private readonly InvoiceDocumentStorage $storage,
    ) {
    }

    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'customer',
            'quotation',
            'items',
        ]);

        $path = 'invoices/' . $invoice->invoice_no . '.pdf';
        $contents = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ])->setPaper('a4')->output();

        return $this->storage->put($path, $contents);
    }
}
