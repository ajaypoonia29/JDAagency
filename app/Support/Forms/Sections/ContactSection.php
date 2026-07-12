<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ContactSection
{
    public static function make(): Section
    {
        return Section::make('Contact Information')
            ->description('Company contact details and address.')
            ->icon('heroicon-o-phone')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel(),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel(),

                        TextInput::make('email')
                            ->label('Primary Email')
                            ->email(),

                        TextInput::make('support_email')
                            ->label('Support Email')
                            ->email(),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->columnSpanFull(),

                        TextInput::make('address_line_1')
                            ->label('Address Line 1')
                            ->columnSpanFull(),

                        TextInput::make('address_line_2')
                            ->label('Address Line 2')
                            ->columnSpanFull(),

                        TextInput::make('city'),

                        TextInput::make('state'),

                        TextInput::make('country'),

                        TextInput::make('pincode'),

                    ]),

            ]);
    }
}