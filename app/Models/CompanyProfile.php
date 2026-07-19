<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        'company_name',
        'company_tagline',
        'owner_name',

        /*
        |--------------------------------------------------------------------------
        | Branding
        |--------------------------------------------------------------------------
        */

        'logo',
        'favicon',
        'primary_color',
        'secondary_color',

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

        'phone',
        'whatsapp',
        'email',
        'support_email',
        'website',

        /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */

        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'pincode',

        /*
        |--------------------------------------------------------------------------
        | Social
        |--------------------------------------------------------------------------
        */

        'facebook',
        'instagram',
        'linkedin',
        'youtube',
        'twitter',

        /*
        |--------------------------------------------------------------------------
        | Financial / Documents
        |--------------------------------------------------------------------------
        */

        'invoice_prefix',
        'receipt_prefix',
        'starting_invoice_number',

        'invoice_footer',
        'receipt_footer',

        'currency',
        'currency_symbol',
        'timezone',
        'date_format',

        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'payment_qr',

        'gst_number',
        'pan_number',

        /*
        |--------------------------------------------------------------------------
        | Communication
        |--------------------------------------------------------------------------
        */

        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',

        'mail_from_name',
        'mail_from_email',
        'mail_mailer',
        'smtp_encryption',
        'test_email',

        'whatsapp_api_key',
        'whatsapp_phone_id',

        /*
        |--------------------------------------------------------------------------
        | System
        |--------------------------------------------------------------------------
        */

        'google_drive_folder',

        'is_active',
    ];

    protected $casts = [

        'is_active' => 'boolean',

        // Encrypt sensitive credentials
        'smtp_password' => 'encrypted',

        'whatsapp_api_key' => 'encrypted',

    ];
}