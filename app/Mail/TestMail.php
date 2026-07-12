<?php

namespace App\Mail;

use App\Services\CompanyService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function build()
    {
        $company = CompanyService::company();

        return $this
            ->subject(
                $company->company_name . ' | SMTP Test'
            )
            ->view('emails.test');
    }
}