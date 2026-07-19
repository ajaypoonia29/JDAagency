<?php

namespace App\Services\Communication;

use App\Services\CompanyService;
use Illuminate\Support\Facades\Config;

class MailConfigurationService
{
    /**
     * Configure Laravel Mail dynamically
     * from the active Company Profile.
     */
    public static function apply(): void
    {
        $company = CompanyService::company();

        Config::set('mail.default', $company->mail_mailer ?: 'smtp');

        Config::set('mail.mailers.smtp.transport', 'smtp');

        Config::set('mail.mailers.smtp.host', $company->smtp_host);

        Config::set('mail.mailers.smtp.port', (int) $company->smtp_port);

        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption);

        Config::set('mail.mailers.smtp.username', $company->smtp_username);

        Config::set('mail.mailers.smtp.password', $company->smtp_password);

        Config::set('mail.from.address', $company->mail_from_email);

        Config::set('mail.from.name', $company->mail_from_name);
    }
}