<?php

namespace App\Services\Documents;

use App\Models\Payment;
use App\Models\Quotation;

class DocumentService
{
    /**
     * Instance-based receipt generation for dependency injection.
     */
    public function generateReceipt(Payment $payment): string
    {
        return static::receipt($payment);
    }

    /**
     * Generate a payment receipt PDF.
     */
    public static function receipt(Payment $payment): string
    {
        return ReceiptGenerator::generate($payment);
    }

    /**
     * Generate a payment statement PDF.
     */
    public static function paymentStatement(Payment $payment): string
    {
        return PaymentStatementGenerator::generate($payment);
    }

    /**
     * Generate a quotation PDF.
     */
    public static function quotation(Quotation $quotation): string
    {
        return QuotationGenerator::generate($quotation);
    }
}