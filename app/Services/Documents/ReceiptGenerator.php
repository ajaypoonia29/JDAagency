<?php

namespace App\Services\Documents;

use App\Models\Payment;
use App\Services\ReceiptService;

class ReceiptGenerator
{
    public static function generate(Payment $payment): string
    {
        return ReceiptService::generate($payment);
    }
}