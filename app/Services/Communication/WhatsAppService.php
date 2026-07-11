<?php

namespace App\Services\Communication;

use App\Models\Payment;

class WhatsAppService
{
    public static function sendReceipt(Payment $payment): bool
    {
        $payment->update([

            'whatsapp_sent' => true,

            'whatsapp_sent_at' => now(),

            'whatsapp_message_id' => 'manual-' . now()->format('YmdHis'),

        ]);

        return true;
    }
}