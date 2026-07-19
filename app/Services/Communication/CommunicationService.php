<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Invoice;
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

    public static function sendInvoiceViaEmail(Invoice $invoice): bool
    {
        return EmailService::sendInvoice($invoice);
    }
}
