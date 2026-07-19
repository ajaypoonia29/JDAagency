<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Refund;
use Barryvdh\DomPDF\Facade\Pdf;

class RefundDocumentService
{
    public function __construct(
        private readonly RefundDocumentStorage $storage,
    ) {
    }

    public function generate(Refund $refund): string
    {
        $refund->loadMissing([
            'invoice',
            'payment',
            'creditNote',
            'customer',
        ]);

        $path = 'refunds/' . $refund->refund_no . '.pdf';
        $contents = Pdf::loadView('pdf.refund', [
            'refund' => $refund,
        ])->setPaper('a4')->output();

        return $this->storage->put($path, $contents);
    }
}
