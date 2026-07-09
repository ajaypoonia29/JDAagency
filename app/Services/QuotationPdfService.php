<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\CompanyProfile;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationPdfService
{
    public function generate(Quotation $quotation)
    {
        $quotation->load([
    'customer',
    'lead',
    'items.service',
]);

$company = CompanyProfile::query()
    ->where('is_active', true)
    ->first();

return Pdf::loadView(
    'pdf.quotation',
    [
        'quotation' => $quotation,
        'company'   => $company,
    ]
)->setPaper('a4');
    }
}