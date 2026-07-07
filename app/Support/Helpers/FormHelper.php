<?php

namespace App\Support\Helpers;

class FormHelper
{
    /**
     * Generate a unique code.
     *
     * Example:
     * EMP-0001
     * CUS-0001
     * INV-0001
     */
    public static function generateCode(string $prefix, int $number): string
    {
        return sprintf('%s-%04d', strtoupper($prefix), $number);
    }
}