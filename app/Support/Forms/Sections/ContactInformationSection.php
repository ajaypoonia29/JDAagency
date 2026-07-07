<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ContactInformationSection
{
    public static function make(): Section
    {
        return Section::make('Contact Information')
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(30),

                        TextInput::make('alternate_phone')
                            ->label('Alternate Phone')
                            ->tel()
                            ->maxLength(30),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(30),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),

                    ]),

            ]);
    }
}