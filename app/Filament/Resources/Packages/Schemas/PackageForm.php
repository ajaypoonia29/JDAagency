<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use App\Models\Service;
use App\Support\PackageCalculator;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackageForm
{
    protected static function updateCalculatedValue($get, $set): void
{
    /*
    |--------------------------------------------------------------------------
    | Inside the repeater we must go up two levels to reach the parent form.
    |--------------------------------------------------------------------------
    */

    $items = $get('../../items') ?? [];

    $result = PackageCalculator::calculate($items);

    $set('../../calculated_value', $result['package_price']);
}

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('General Information')
                    ->schema([

                        Grid::make([
    'default' => 1,
    'md' => 2,
])
                            ->schema([

                                TextInput::make('package_code')
                                    ->required(),

                                TextInput::make('package_name')
                                    ->required(),

                                TextInput::make('slug')
                                    ->required(),

                                TextInput::make('category'),

                            ]),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),

                    ]),

                Section::make('Pricing')
                    ->schema([

                        Grid::make([
    'default' => 1,
    'md' => 2,
    'xl' => 3,
])
                            ->schema([

                                TextInput::make('package_price')
                                    ->numeric()
                                    ->prefix('₹'),

                                TextInput::make('discount_amount')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₹'),

                                TextInput::make('final_price')
    ->numeric()
    ->prefix('₹')
    ->readOnly(),

TextInput::make('calculated_value')
    ->label('Calculated Package Value')
    ->numeric()
    ->prefix('₹')
    ->readOnly(),

                                Toggle::make('gst_applicable')
                                    ->default(true),

                                TextInput::make('gst_percentage')
                                    ->numeric()
                                    ->default(18)
                                    ->suffix('%'),

                                Toggle::make('featured'),

                            ]),

                    ]),
Section::make('Services Included')
    ->schema([

        Repeater::make('items')
            ->relationship()
            ->defaultItems(1)
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->schema([

                Grid::make([
    'default' => 1,
    'md' => 2,
    'xl' => 12,
])
                    ->schema([

                        Select::make('service_id')
    ->relationship('service', 'service_name')
    ->searchable()
    ->preload()
    ->live()
    ->required()
    ->afterStateUpdated(function (?string $state, $get, $set) {

        if (! $state) {
            self::updateCalculatedValue($get, $set);
            return;
        }

        $service = Service::find($state);

        if (! $service) {
            self::updateCalculatedValue($get, $set);
            return;
        }

        $set('custom_price', $service->standard_price);

        self::updateCalculatedValue($get, $set);

    })
->columnSpan([
        'default' => 1,
        'md' => 2,
        'xl' => 6,
    ]),

                        TextInput::make('quantity')
    ->numeric()
    ->default(1)
    ->required()
    ->live()
    ->afterStateUpdated(
        fn ($get, $set) =>
            self::updateCalculatedValue($get, $set)
    )
    ->columnSpan([
        'default' => 1,
        'xl' => 2,
    ]),

                        TextInput::make('custom_price')
    ->numeric()
    ->prefix('₹')
    ->helperText('Leave empty to use the service price.')
    ->live()
    ->afterStateUpdated(
        fn ($get, $set) =>
            self::updateCalculatedValue($get, $set)
    )
    ->columnSpan([
        'default' => 1,
        'xl' => 3,
    ]),

                        Toggle::make('is_optional')
    ->label('Optional')
    ->columnSpan([
        'default' => 1,
        'xl' => 1,
    ]),

                    ]),

            ])
            ->columnSpanFull(),

    ]),

                Section::make('Marketing')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                FileUpload::make('thumbnail')
                                    ->directory('packages')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public'),

                                TextInput::make('display_order')
                                    ->numeric()
                                    ->default(0),

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