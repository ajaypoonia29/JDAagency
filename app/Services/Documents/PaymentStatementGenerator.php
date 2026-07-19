<?php

namespace App\Services\Documents;

use App\Models\Payment;
use App\Services\PaymentStatementService;

class PaymentStatementGenerator
{
    public static function generate(Payment $payment): string
    {
        return PaymentStatementService::generate($payment);
    }
}