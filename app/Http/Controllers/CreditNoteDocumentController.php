<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Services\Documents\CreditNoteDocumentStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditNoteDocumentController extends Controller
{
    public function download(
        CreditNote $creditNote,
        CreditNoteDocumentStorage $storage,
    ): BinaryFileResponse {
        Gate::authorize('download', $creditNote);
        abort_if(blank($creditNote->credit_note_pdf), 404);

        return $storage->download(
            $creditNote->credit_note_pdf,
            $creditNote->credit_note_no . '.pdf',
        );
    }
}
