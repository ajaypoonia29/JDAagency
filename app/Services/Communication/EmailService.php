<?php

namespace App\Services\Communication;

use App\Mail\ReceiptMail;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailService
{
    public static function sendReceipt(Payment $payment): bool
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Apply Company SMTP Settings
            |--------------------------------------------------------------------------
            */

            MailConfigurationService::apply();

            /*
            |--------------------------------------------------------------------------
            | Validate Recipient
            |--------------------------------------------------------------------------
            */

            $email = $payment->customer?->primary_email;

            if (blank($email)) {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Send Receipt
            |--------------------------------------------------------------------------
            */

            Mail::to($email)
                ->send(new ReceiptMail($payment));

            /*
            |--------------------------------------------------------------------------
            | Update Tracking
            |--------------------------------------------------------------------------
            */

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