<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReceiptService
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

        $fileName = $payment->receipt_number . '.pdf';

        $relativePath = 'receipts/' . $fileName;

        $absolutePath = Storage::disk('local')->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        Pdf::loadView('pdf.receipt', [

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

            'receipt_pdf' => $relativePath,

            'receipt_generated_at' => $payment->receipt_generated_at ?? now(),

        ]);

        return $relativePath;
    }
}