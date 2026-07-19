<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
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

        TextInput::make('sku')
            ->label('SKU')
            ->maxLength(100),

        TextInput::make('service_name')
            ->required()
            ->maxLength(255),

        TextInput::make('short_name')
            ->maxLength(100),

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

        Select::make('pricing_type')
            ->options([
                'fixed' => 'Fixed',
                'hourly' => 'Hourly',
                'monthly' => 'Monthly',
                'custom' => 'Custom',
            ])
            ->default('fixed')
            ->required(),

        TextInput::make('standard_price')
            ->numeric()
            ->prefix('₹')
            ->required(),

        TextInput::make('setup_fee')
            ->numeric()
            ->default(0)
            ->prefix('₹'),

        TextInput::make('monthly_price')
            ->numeric()
            ->default(0)
            ->prefix('₹'),

    ]),

                        Textarea::make('description')
    ->rows(4)
    ->columnSpanFull(),

Grid::make(2)
    ->schema([

        FileUpload::make('thumbnail')
            ->directory('services')
            ->disk('public')
            ->image()
            ->imageEditor()
            ->visibility('public'),

        TextInput::make('icon')
            ->helperText('Example: heroicon-o-globe-alt'),

    ]),

Textarea::make('notes')
    ->rows(4)
    ->columnSpanFull(),

                    ]),

                Section::make('Pricing, GST & Delivery')
    ->schema([

        Grid::make(3)
            ->schema([

                Toggle::make('recurring')
                    ->label('Recurring Service'),

                Toggle::make('gst_applicable')
                    ->label('GST Applicable')
                    ->live(),

                TextInput::make('gst_percentage')
                    ->numeric()
                    ->default(18)
                    ->suffix('%')
                    ->visible(fn ($get) => $get('gst_applicable')),

                TextInput::make('estimated_duration')
                    ->numeric()
                    ->suffix('Days'),

                TextInput::make('display_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_featured')
                    ->label('Featured Service'),

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