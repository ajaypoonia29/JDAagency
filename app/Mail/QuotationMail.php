<?php

namespace App\Mail;

use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Quotation $quotation
    ) {
    }

    public function build()
    {
        $pdf = app(QuotationPdfService::class)
            ->generate($this->quotation);

        return $this
            ->subject(
                'Quotation - ' . $this->quotation->quotation_code
            )
            ->view('emails.quotation')
            ->attachData(
                $pdf->output(),
                $this->quotation->quotation_code . '.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}