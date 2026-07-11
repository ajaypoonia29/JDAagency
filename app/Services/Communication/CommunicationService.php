<?php

namespace App\Services\Communication;

use App\Models\Payment;

class CommunicationService
{
    public static function sendReceiptViaWhatsApp(Payment $payment): bool
    {
        return WhatsAppService::sendReceipt($payment);
    }

    public static function sendReceiptViaEmail(Payment $payment): bool
    {
        return EmailService::sendReceipt($payment);
    }

    public static function sendReceipt(Payment $payment): bool
    {
        $whatsapp = static::sendReceiptViaWhatsApp($payment);

        $email = static::sendReceiptViaEmail($payment);

        return $whatsapp && $email;
    }
}