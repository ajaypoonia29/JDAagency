<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Services\Documents\RefundDocumentStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefundDocumentController extends Controller
{
    public function download(
        Refund $refund,
        RefundDocumentStorage $storage,
    ): BinaryFileResponse {
        Gate::authorize('download', $refund);
        abort_if(blank($refund->refund_pdf), 404);

        return $storage->download(
            $refund->refund_pdf,
            $refund->refund_no . '.pdf',
        );
    }
}
