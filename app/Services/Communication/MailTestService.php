<?php

namespace App\Services\Communication;

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTestService
{
    /**
     * Send a branded test email using the active
     * Company Profile SMTP configuration.
     */
    public static function send(string $recipient): array
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Apply Runtime SMTP Configuration
            |--------------------------------------------------------------------------
            */

            MailConfigurationService::apply();

            /*
            |--------------------------------------------------------------------------
            | Send Test Email
            |--------------------------------------------------------------------------
            */

            Mail::to($recipient)
                ->send(new TestMail());

            return [

                'success' => true,

                'message' => 'Test email sent successfully.',

            ];

        } catch (Throwable $exception) {

            report($exception);

            return [

                'success' => false,

                'message' => $exception->getMessage(),

            ];

        }
    }
}