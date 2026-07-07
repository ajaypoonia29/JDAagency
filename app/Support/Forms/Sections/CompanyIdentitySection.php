<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CompanyIdentitySection
{
    public static function make(): Section
    {
        return Section::make('Company Identity')
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),

                        TextInput::make('contact_person')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('designation')
                            ->label('Designation')
                            ->maxLength(255),

                    ]),

            ]);
    }
}