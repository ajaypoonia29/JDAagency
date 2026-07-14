<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNoteDocumentService
{
    public function __construct(
        private readonly CreditNoteDocumentStorage $storage,
    ) {
    }

    public function generate(CreditNote $creditNote): string
    {
        $creditNote->loadMissing([
            'invoice',
            'customer',
            'items',
        ]);

        $path = 'credit-notes/' . $creditNote->credit_note_no . '.pdf';
        $contents = Pdf::loadView('pdf.credit-note', [
            'creditNote' => $creditNote,
        ])->setPaper('a4')->output();

        return $this->storage->put($path, $contents);
    }
}
