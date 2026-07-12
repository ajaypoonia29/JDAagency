<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SystemSection
{
    public static function make(): Section
    {
        return Section::make('System Settings')
            ->description('Application and storage configuration.')
            ->icon('heroicon-o-cog-6-tooth')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('google_drive_folder')
                            ->label('Google Drive Folder'),

                        Toggle::make('is_active')
                            ->label('Active Company')
                            ->default(true),

                    ]),

            ]);
    }
}