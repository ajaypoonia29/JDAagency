<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Documents\DocumentService;
use App\Services\Documents\PaymentDocumentStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentDocumentController extends Controller
{
    public function receipt(
        Payment $payment,
        PaymentDocumentStorage $documents,
    ): BinaryFileResponse {
        Gate::authorize('downloadReceipt', $payment);

        abort_unless(
            $payment->receipt_generated
            && filled($payment->receipt_pdf),
            404,
        );

        return $documents->download(
            $payment->receipt_pdf,
            ($payment->receipt_number ?: $payment->payment_no)
                . '.pdf',
        );
    }

    public function statement(
        Payment $payment,
        DocumentService $generator,
        PaymentDocumentStorage $documents,
    ): BinaryFileResponse {
        Gate::authorize('downloadStatement', $payment);

        abort_unless($payment->receipt_generated, 404);

        if (
            blank($payment->statement_pdf)
            || ! $documents->exists($payment->statement_pdf)
        ) {
            $generator->generatePaymentStatement($payment);
            $payment->refresh();
        }

        abort_unless(filled($payment->statement_pdf), 404);

        return $documents->download(
            $payment->statement_pdf,
            'STATEMENT-'
                . ($payment->receipt_number ?: $payment->payment_no)
                . '.pdf',
        );
    }
}
