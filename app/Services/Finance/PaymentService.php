<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Services\Documents\DocumentService;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Complete the payment workflow safely.
     */
    public static function process(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            // Update payment ledger & payment status.
            $payment->completePayment();

            // Generate receipt PDF.
            DocumentService::receipt($payment);

            /*
            |--------------------------------------------------------------------------
            | Future Integrations
            |--------------------------------------------------------------------------
            |
            | CommunicationService::sendReceiptViaEmail($payment);
            | CommunicationService::sendReceiptViaWhatsApp($payment);
            |
            */

        });
    }
}