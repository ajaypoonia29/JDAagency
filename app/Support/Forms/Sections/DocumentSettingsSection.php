<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class DocumentSettingsSection
{
    public static function make(): Section
    {
        return Section::make('Document Settings')
            ->description('Invoice, receipt and regional document configuration.')
            ->icon('heroicon-o-document-text')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('invoice_prefix')
                            ->label('Invoice Prefix')
                            ->required()
                            ->default('INV'),

                        TextInput::make('receipt_prefix')
                            ->label('Receipt Prefix')
                            ->required()
                            ->default('REC'),

                        TextInput::make('starting_invoice_number')
                            ->label('Starting Invoice Number')
                            ->numeric()
                            ->required()
                            ->default(1001),

                        TextInput::make('currency')
                            ->required()
                            ->default('INR'),

                        TextInput::make('currency_symbol')
                            ->required()
                            ->default('₹'),

                        TextInput::make('timezone')
                            ->required()
                            ->default('Asia/Kolkata'),

                        TextInput::make('date_format')
                            ->required()
                            ->default('d-m-Y'),

                    ]),

                Textarea::make('invoice_footer')
                    ->label('Invoice Footer')
                    ->rows(4),

                Textarea::make('receipt_footer')
                    ->label('Receipt Footer')
                    ->rows(4),

            ]);
    }
}