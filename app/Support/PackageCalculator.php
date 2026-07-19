<?php

namespace App\Support;

class PackageCalculator
{
    public static function calculate(array $items): array
    {
        $packagePrice = 0;

        foreach ($items as $item) {

            $quantity = (float) ($item['quantity'] ?? 0);

            $price = $item['custom_price'] ?? 0;

            $price = (float) $price;

            $packagePrice += ($quantity * $price);
        }

        return [

            'package_price' => round($packagePrice, 2),

        ];
    }
}