<?php

namespace App\Services\Documents;

use App\Models\Quotation;
use App\Services\QuotationPdfService;

class QuotationGenerator
{
    public static function generate(Quotation $quotation): string
    {
        return QuotationPdfService::generate($quotation);
    }
}