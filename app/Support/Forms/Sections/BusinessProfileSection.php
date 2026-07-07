<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BusinessProfileSection
{
    public static function make(): Section
    {
        return Section::make('Business Profile')
            ->schema([

                Grid::make(3)
                    ->schema([

                        TextInput::make('industry')
                            ->label('Industry')
                            ->maxLength(255),

                        TextInput::make('business_type')
                            ->label('Business Type')
                            ->maxLength(255),

                        TextInput::make('business_category')
                            ->label('Business Category')
                            ->maxLength(255),

                        TextInput::make('company_size')
                            ->label('Company Size')
                            ->maxLength(255),

                    ]),

            ]);
    }
}