<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FinancialSection
{
    public static function make(): Section
    {
        return Section::make('Financial Information')
            ->description('Tax registration and banking details.')
            ->icon('heroicon-o-banknotes')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('gst_number')
                            ->label('GST Number'),

                        TextInput::make('pan_number')
                            ->label('PAN Number'),

                        TextInput::make('bank_name')
                            ->label('Bank Name'),

                        TextInput::make('account_name')
                            ->label('Account Name'),

                        TextInput::make('account_number')
                            ->label('Account Number'),

                        TextInput::make('ifsc_code')
                            ->label('IFSC Code'),

                        TextInput::make('upi_id')
                            ->label('UPI ID'),

                        TextInput::make('payment_qr')
                            ->label('Payment QR'),

                    ]),

            ]);
    }
}