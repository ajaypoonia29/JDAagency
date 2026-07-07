<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Customer Identity')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('customer_code')
                                    ->default(fn () => Customer::nextCustomerCode())
                                    ->readOnly()
                                    ->required(),

                                Select::make('customer_type')
                                    ->options([
                                        'Individual' => 'Individual',
                                        'Business' => 'Business',
                                        'Government' => 'Government',
                                    ])
                                    ->default('Business')
                                    ->required(),

                                Select::make('customer_status')
                                    ->options([
                                        'Lead' => 'Lead',
                                        'Prospect' => 'Prospect',
                                        'Active' => 'Active',
                                        'Inactive' => 'Inactive',
                                        'Blacklisted' => 'Blacklisted',
                                    ])
                                    ->default('Lead')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Company Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('company_name'),

                                TextInput::make('legal_name'),

                                TextInput::make('display_name')
                                    ->required(),

                                TextInput::make('contact_person')
                                    ->required(),

                                TextInput::make('designation'),

                                TextInput::make('industry'),

                                TextInput::make('business_category'),

                            ]),
                    ]),

                Section::make('Contact Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('primary_email')
                                    ->email()
                                    ->required(),

                                TextInput::make('secondary_email')
                                    ->email(),

                                TextInput::make('primary_phone')
                                    ->tel()
                                    ->required(),

                                TextInput::make('alternate_phone')
                                    ->tel(),

                                TextInput::make('whatsapp'),

                                TextInput::make('website')
                                    ->url(),

                            ]),
                    ]),

                Section::make('Tax Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('gst_number'),

                                TextInput::make('pan_number'),

                                TextInput::make('cin_number'),

                                TextInput::make('tan_number'),

                                TextInput::make('msme_number'),

                            ]),
                    ]),

                Section::make('Billing Address')
                    ->schema([

                        Textarea::make('billing_address')
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([

                                TextInput::make('billing_city'),

                                TextInput::make('billing_state'),

                                TextInput::make('billing_country'),

                                TextInput::make('billing_pincode'),

                            ]),
                    ]),

                Section::make('Shipping Address')
                    ->schema([

                        Toggle::make('same_as_billing')
                            ->default(true),

                        Textarea::make('shipping_address')
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([

                                TextInput::make('shipping_city'),

                                TextInput::make('shipping_state'),

                                TextInput::make('shipping_country'),

                                TextInput::make('shipping_pincode'),

                            ]),
                    ]),

                Section::make('Sales Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                Select::make('assigned_employee_id')
                                    ->relationship('assignedEmployee', 'full_name')
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('lead_source'),

                                TextInput::make('credit_limit')
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('payment_terms'),

                                TextInput::make('currency')
                                    ->default('INR'),

                            ]),
                    ]),

                Section::make('Documents')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                FileUpload::make('company_logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('customers/logo'),

                                FileUpload::make('gst_certificate')
                                    ->disk('public')
                                    ->directory('customers/gst'),

                                FileUpload::make('pan_document')
                                    ->disk('public')
                                    ->directory('customers/pan'),

                                FileUpload::make('business_license')
                                    ->disk('public')
                                    ->directory('customers/license'),

                                FileUpload::make('agreement')
                                    ->disk('public')
                                    ->directory('customers/agreement'),

                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([

                        Textarea::make('notes')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true),

                    ]),

            ]);
    }
}