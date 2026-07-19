<?php

namespace App\Services;

use App\Models\CompanyProfile;

class CompanyService
{
    public static function company(): CompanyProfile
    {
        return CompanyProfile::query()
            ->where('is_active', true)
            ->firstOrFail();
    }
}