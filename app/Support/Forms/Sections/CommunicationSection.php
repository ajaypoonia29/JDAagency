<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CommunicationSection
{
    public static function make(): Section
    {
        return Section::make('Communication')
            ->description('Configure email, SMTP and messaging services.')
            ->schema([

                Grid::make(2)
                    ->schema([

                        Select::make('mail_mailer')
                            ->label('Mail Driver')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Log',
                            ])
                            ->default('smtp')
                            ->required(),

                        Select::make('smtp_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls'),

                        TextInput::make('smtp_host')
                            ->label('SMTP Host'),

                        TextInput::make('smtp_port')
                            ->numeric(),

                        TextInput::make('smtp_username'),

                        TextInput::make('smtp_password')
                            ->password()
                            ->revealable(),

                        TextInput::make('mail_from_name'),

                        TextInput::make('mail_from_email')
                            ->email(),

                        TextInput::make('test_email')
                            ->email(),

                        TextInput::make('whatsapp_phone_id'),

                        TextInput::make('whatsapp_api_key')
                            ->password()
                            ->revealable(),

                        Toggle::make('is_active'),

                    ]),

            ])
            ->collapsible();
    }
}