<?php

namespace App\Services\Communication;

class SmsService
{
    public static function send(string $mobile, string $message): bool
    {
        /*
        |--------------------------------------------------------------------------
        | SMS Gateway
        |--------------------------------------------------------------------------
        |
        | Twilio / MSG91 / Textlocal integration later.
        |
        */

        return true;
    }
}