<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Service Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('service_code')
                                    ->default(fn () => Service::nextServiceCode())
                                    ->readOnly()
                                    ->required(),

                                TextInput::make('service_name')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('category')
                                    ->options([
                                        'Website Development' => 'Website Development',
                                        'SEO' => 'SEO',
                                        'Google Business Profile' => 'Google Business Profile',
                                        'Social Media Marketing' => 'Social Media Marketing',
                                        'Meta Ads' => 'Meta Ads',
                                        'Google Ads' => 'Google Ads',
                                        'Graphic Design' => 'Graphic Design',
                                        'Photography' => 'Photography',
                                        'Videography' => 'Videography',
                                        'Content Writing' => 'Content Writing',
                                        'Branding' => 'Branding',
                                        'Other' => 'Other',
                                    ])
                                    ->searchable()
                                    ->required(),

                                TextInput::make('standard_price')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->required(),

                            ]),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),

                    ]),

                Section::make('GST & Delivery')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                Toggle::make('gst_applicable')
                                    ->live(),

                                TextInput::make('gst_percentage')
                                    ->numeric()
                                    ->default(18)
                                    ->visible(fn ($get) => $get('gst_applicable')),

                                TextInput::make('estimated_duration')
                                    ->numeric()
                                    ->suffix('Days'),

                            ]),

                    ]),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->default(true),

                    ]),

            ]);
    }
}