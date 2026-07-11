<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentStatementService
{
    public static function generate(Payment $payment): string
    {
        $company = CompanyService::company();

        /*
        |--------------------------------------------------------------------------
        | Generate QR Code (Base64)
        |--------------------------------------------------------------------------
        */

        $verificationUrl = route('receipt.verify', [
            'hash' => $payment->verification_hash,
        ]);

        $qrCode = base64_encode(
    QrCode::format('svg')
        ->size(220)
        ->margin(1)
        ->generate($verificationUrl)
);

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $fileName = 'STATEMENT-' . $payment->receipt_number . '.pdf';

        $relativePath = 'payment-statements/' . $fileName;

        $absolutePath = storage_path('app/public/' . $relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        Pdf::loadView('pdf.payment-statement', [

            'payment' => $payment->fresh([
                'customer',
                'quotation',
            ]),

            'company' => $company,

            'qrCode' => $qrCode,

            'verificationUrl' => $verificationUrl,

        ])
        ->setPaper('a4')
        ->save($absolutePath);

        $payment->update([

    'statement_pdf' => $relativePath,

    'statement_generated_at' => now(),

]);

        return $relativePath;
    }
}