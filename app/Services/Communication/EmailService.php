<?php

namespace App\Services\Communication;

use App\Models\Payment;

class EmailService
{
    public static function sendReceipt(Payment $payment): bool
    {
        $payment->update([

            'email_sent' => true,

            'email_sent_at' => now(),

            'email_message_id' => 'manual-' . now()->format('YmdHis'),

        ]);

        return true;
    }
}