<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\CompanyService;
use App\Services\Documents\PaymentDocumentStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Payment $payment
    ) {
    }

    public function build()
    {
        $company = CompanyService::company();

        $mail = $this
            ->subject(
                $company->company_name .
                ' | Payment Receipt - ' .
                $this->payment->receipt_number
            )
            ->view('emails.receipt');

        $path = app(PaymentDocumentStorage::class)
            ->absolutePath($this->payment->receipt_pdf);

        if ($path) {
            $mail->attach(
                $path,
                [
                    'as' => $this->payment->receipt_number . '.pdf',
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mail;
    }
}