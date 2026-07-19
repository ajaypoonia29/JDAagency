<?php

namespace App\Filament\Resources\CompanyProfiles\Schemas;

use App\Support\Forms\Sections\BrandingSection;
use App\Support\Forms\Sections\CommunicationSection;
use App\Support\Forms\Sections\CompanyIdentitySection;
use App\Support\Forms\Sections\ContactSection;
use App\Support\Forms\Sections\DocumentSettingsSection;
use App\Support\Forms\Sections\FinancialSection;
use App\Support\Forms\Sections\SocialMediaSection;
use App\Support\Forms\Sections\SystemSection;
use Filament\Schemas\Schema;

class CompanyProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                CompanyIdentitySection::make(),

                BrandingSection::make(),

                ContactSection::make(),

                FinancialSection::make(),

                DocumentSettingsSection::make(),

                CommunicationSection::make(),

                SocialMediaSection::make(),

                SystemSection::make(),

            ]);
    }
}