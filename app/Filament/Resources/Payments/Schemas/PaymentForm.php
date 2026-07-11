<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\Quotation;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Payment Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                Placeholder::make('payment_no_preview')
                                    ->label('Payment Number')
                                    ->content(fn () => Payment::nextPaymentNumber()),

                                Placeholder::make('receipt_no_preview')
                                    ->label('Receipt Number')
                                    ->content(fn () => Payment::nextReceiptNumber()),

                                DatePicker::make('payment_date')
                                    ->default(now())
                                    ->required(),

                                

Select::make('quotation_id')
    ->label('Quotation')
    ->relationship('quotation', 'quotation_code')
    ->searchable()
    ->preload()
    ->live()
	->disabled(fn () => request()->has('quotation'))
	->dehydrated()
    ->afterStateUpdated(function (Set $set, $state): void {

    if (! $state) {

        $set('customer_id', null);
        $set('amount', null);

        return;
    }

    $quotation = Quotation::find($state);

    if (! $quotation) {
        return;
    }

    $set('customer_id', $quotation->customer_id);
    $set('amount', $quotation->grand_total);

})
    ->required(),

                                
Select::make('customer_id')
    ->label('Customer')
    ->relationship('customer', 'display_name')
    ->disabled()
    ->dehydrated()
    ->searchable()
    ->preload(),

TextInput::make('amount')
    ->prefix('₹')
    ->numeric()
    ->disabled()
    ->dehydrated(),



                                Select::make('payment_method')
                                    ->options([
                                        'Cash' => 'Cash',
                                        'UPI' => 'UPI',
                                        'Bank Transfer' => 'Bank Transfer',
                                        'Cheque' => 'Cheque',
                                        'Credit Card' => 'Credit Card',
                                        'Debit Card' => 'Debit Card',
                                    ])
                                    ->required(),

                                TextInput::make('transaction_reference')
                                    ->label('Transaction Reference'),

                            ]),

                    ]),

                Section::make('Notes')
                    ->schema([

                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),

                    ]),

                Section::make('Communication Status')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                Toggle::make('receipt_generated')
                                    ->disabled(),

                                Toggle::make('whatsapp_sent')
                                    ->disabled(),

                                Toggle::make('email_sent')
                                    ->disabled(),

                                Toggle::make('is_active')
                                    ->default(true),

                            ]),

                    ]),

            ]);
    }
}