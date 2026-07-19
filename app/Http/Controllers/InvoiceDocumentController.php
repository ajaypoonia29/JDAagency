<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Documents\InvoiceDocumentStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceDocumentController extends Controller
{
    public function download(
        Invoice $invoice,
        InvoiceDocumentStorage $storage,
    ): BinaryFileResponse {
        Gate::authorize('download', $invoice);

        abort_if(
            blank($invoice->invoice_pdf),
            404,
        );

        return $storage->download(
            $invoice->invoice_pdf,
            $invoice->invoice_no . '.pdf',
        );
    }
}
