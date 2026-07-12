<?php

namespace App\Services\Communication;

use App\Mail\QuotationMail;
use App\Mail\ReceiptMail;
use App\Models\Payment;
use App\Models\Quotation;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailService
{
    /**
     * Send quotation email.
     */
    public static function sendQuotation(Quotation $quotation): bool
    {
        try {

            MailConfigurationService::apply();

            $email = $quotation->customer?->primary_email;

            if (blank($email)) {
                return false;
            }

            Mail::to($email)
                ->send(new QuotationMail($quotation));

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

    /**
     * Send payment receipt.
     */
    public static function sendReceipt(Payment $payment): bool
    {
        try {

            MailConfigurationService::apply();

            $email = $payment->customer?->primary_email;

            if (blank($email)) {
                return false;
            }

            Mail::to($email)
                ->send(new ReceiptMail($payment));

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
}