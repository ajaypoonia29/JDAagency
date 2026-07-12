<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\CompanyService;
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

        if (
            $this->payment->receipt_pdf &&
            storage_path('app/public/' . $this->payment->receipt_pdf)
        ) {

            $path = storage_path(
                'app/public/' . $this->payment->receipt_pdf
            );

            if (file_exists($path)) {

                $mail->attach(
                    $path,
                    [
                        'as' => $this->payment->receipt_number . '.pdf',
                        'mime' => 'application/pdf',
                    ]
                );

            }

        }

        return $mail;
    }
}