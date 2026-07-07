<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'company_name',
        'company_tagline',
        'owner_name',

        'logo',
        'favicon',
        'primary_color',
        'secondary_color',

        'phone',
        'whatsapp',
        'email',
        'support_email',
        'website',

        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'pincode',

        'facebook',
        'instagram',
        'linkedin',
        'youtube',
        'twitter',

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

        'google_drive_folder',

        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',

        'whatsapp_api_key',
        'whatsapp_phone_id',

        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}