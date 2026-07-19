<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Mail\InvoiceMail;
use App\Mail\QuotationMail;
use App\Mail\ReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Documents\InvoiceDocumentRefreshService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmailService
{
    public static function sendQuotation(Quotation $quotation): bool
    {
        try {
            MailConfigurationService::apply();

            $email = $quotation->customer?->primary_email;

            if (blank($email)) {
                return false;
            }

            Mail::to($email)->send(new QuotationMail($quotation));

            $quotation->update([
                'quotation_sent_at' => now(),
                'quotation_sent_by' => auth()->id(),
                'quotation_send_count' => $quotation->quotation_send_count + 1,
                'last_sent_to' => $email,
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public static function sendReceipt(Payment $payment): bool
    {
        try {
            MailConfigurationService::apply();

            $email = $payment->customer?->primary_email;

            if (blank($email)) {
                return false;
            }

            Mail::to($email)->send(new ReceiptMail($payment));

            $payment->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_message_id' => 'mail-' . now()->format('YmdHis'),
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public static function sendInvoice(Invoice $invoice): bool
    {
        $email = $invoice->customer?->primary_email;

        $invoice->forceFill([
            'last_delivery_attempt_at' => now(),
            'last_delivery_error' => null,
        ])->saveQuietly();

        if (
            blank($email)
            || ! $invoice->issued_at
            || blank($invoice->invoice_pdf)
            || $invoice->status === 'Void'
        ) {
            $invoice->forceFill([
                'last_delivery_error' => blank($email)
                    ? 'The customer does not have a primary email address.'
                    : 'Only issued invoices with a generated PDF can be emailed.',
            ])->saveQuietly();

            return false;
        }

        try {
            MailConfigurationService::apply();
            app(InvoiceDocumentRefreshService::class)->regenerate(
                $invoice->refresh(),
            );
            Mail::to($email)->send(new InvoiceMail($invoice->refresh()));

            $invoice->forceFill([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_sent_by' => auth()->id(),
                'email_send_count' => (int) $invoice->email_send_count + 1,
                'last_sent_to' => $email,
                'email_message_id' => 'invoice-' . Str::uuid(),
                'last_delivery_error' => null,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            $invoice->forceFill([
                'last_delivery_error' =>
                    'Mail delivery failed. Check the application log.',
            ])->saveQuietly();

            report($exception);

            return false;
        }
    }
}
