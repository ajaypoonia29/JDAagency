<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BrandingSection
{
    public static function make(): Section
    {
        return Section::make('Branding')
            ->description('Configure your company branding.')
            ->icon('heroicon-o-paint-brush')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('primary_color')
                            ->label('Primary Color')
                            ->required()
                            ->default('#2563eb'),

                        TextInput::make('secondary_color')
                            ->label('Secondary Color')
                            ->required()
                            ->default('#1e293b'),

                    ]),

            ]);
    }
}