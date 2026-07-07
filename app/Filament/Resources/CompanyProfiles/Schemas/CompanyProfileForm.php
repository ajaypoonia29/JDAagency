<?php

namespace App\Filament\Resources\CompanyProfiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('company_tagline'),
                TextInput::make('owner_name'),
                TextInput::make('logo'),
                TextInput::make('favicon'),
                TextInput::make('primary_color')
                    ->required()
                    ->default('#2563eb'),
                TextInput::make('secondary_color')
                    ->required()
                    ->default('#1e293b'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('whatsapp'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('support_email')
                    ->email(),
                TextInput::make('website')
                    ->url(),
                TextInput::make('address_line_1'),
                TextInput::make('address_line_2'),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('country'),
                TextInput::make('pincode'),
                TextInput::make('facebook'),
                TextInput::make('instagram'),
                TextInput::make('linkedin'),
                TextInput::make('youtube'),
                TextInput::make('twitter'),
                TextInput::make('invoice_prefix')
                    ->required()
                    ->default('INV'),
                TextInput::make('receipt_prefix')
                    ->required()
                    ->default('REC'),
                TextInput::make('starting_invoice_number')
                    ->required()
                    ->numeric()
                    ->default(1001),
                Textarea::make('invoice_footer')
                    ->columnSpanFull(),
                Textarea::make('receipt_footer')
                    ->columnSpanFull(),
                TextInput::make('currency')
                    ->required()
                    ->default('INR'),
                TextInput::make('currency_symbol')
                    ->required()
                    ->default('₹'),
                TextInput::make('timezone')
                    ->required()
                    ->default('Asia/Kolkata'),
                TextInput::make('date_format')
                    ->required()
                    ->default('d-m-Y'),
                TextInput::make('bank_name'),
                TextInput::make('account_name'),
                TextInput::make('account_number'),
                TextInput::make('ifsc_code'),
                TextInput::make('upi_id'),
                TextInput::make('payment_qr'),
                TextInput::make('google_drive_folder'),
                TextInput::make('smtp_host'),
                TextInput::make('smtp_port'),
                TextInput::make('smtp_username'),
                Textarea::make('smtp_password')
                    ->columnSpanFull(),
                Textarea::make('whatsapp_api_key')
                    ->columnSpanFull(),
                TextInput::make('whatsapp_phone_id')
                    ->tel(),
                TextInput::make('gst_number'),
                TextInput::make('pan_number'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
