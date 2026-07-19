<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CompanyIdentitySection
{
    public static function make(): Section
    {
        return Section::make('Company Information')
            ->description('Basic information about your business.')
            ->icon('heroicon-o-building-office-2')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('owner_name')
                            ->label('Owner / Director')
                            ->maxLength(255),

                        TextInput::make('company_tagline')
                            ->label('Tagline')
                            ->columnSpanFull(),

                        FileUpload::make('logo')
                            ->label('Company Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('company/logo')
                            ->visibility('public')
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('company/favicon')
                            ->visibility('public')
                            ->openable()
                            ->downloadable(),

                    ]),

            ]);
    }
}