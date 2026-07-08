<?php

namespace App\Support;

class QuotationCalculator
{
    public static function calculate(
        array $items,
        string $discountType = 'fixed',
        float $discountValue = 0,
        bool $taxApplicable = false,
        float $taxPercentage = 18,
    ): array {

        $subtotal = collect($items)
            ->sum(fn ($item) => (float) ($item['line_total'] ?? 0));

        $discountAmount = $discountType === 'percentage'
            ? ($subtotal * $discountValue / 100)
            : $discountValue;

        $discountAmount = min($discountAmount, $subtotal);

        $taxableAmount = $subtotal - $discountAmount;

        $tax = 0;

        if ($taxApplicable) {
            $tax = ($taxableAmount * $taxPercentage) / 100;
        }

        $grandTotal = $taxableAmount + $tax;

        return [

            'subtotal' => round($subtotal, 2),

            'discount_amount' => round($discountAmount, 2),

            'tax' => round($tax, 2),

            'grand_total' => round($grandTotal, 2),

        ];
    }
}