<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Services\CompanyService;
use App\Services\Documents\InvoiceDocumentStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {
    }

    public function build(): self
    {
        $company = CompanyService::company();

        $mail = $this
            ->subject(
                $company->company_name
                . ' | Invoice - '
                . $this->invoice->invoice_no,
            )
            ->view('emails.invoice');

        $path = app(InvoiceDocumentStorage::class)
            ->absolutePath($this->invoice->invoice_pdf);

        if ($path) {
            $mail->attach($path, [
                'as' => $this->invoice->invoice_no . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
