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
     * Instance-based payment statement generation.
     */
    public function generatePaymentStatement(Payment $payment): string
    {
        return static::paymentStatement($payment);
    }

    /**
     * Generate a payment receipt PDF and move it to private storage.
     */
    public static function receipt(Payment $payment): string
    {
        $path = ReceiptGenerator::generate($payment);

        return app(PaymentDocumentStorage::class)
            ->privatize($path);
    }

    /**
     * Generate a payment statement PDF and move it to private storage.
     */
    public static function paymentStatement(Payment $payment): string
    {
        $path = PaymentStatementGenerator::generate($payment);

        return app(PaymentDocumentStorage::class)
            ->privatize($path);
    }

    /**
     * Generate a quotation PDF.
     */
    public static function quotation(Quotation $quotation): string
    {
        return QuotationGenerator::generate($quotation);
    }
}
