<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('employee_code')
                    ->required(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('alternate_phone')
                    ->tel(),
                TextInput::make('department'),
                TextInput::make('designation'),
                DatePicker::make('joining_date'),
                TextInput::make('employment_status')
                    ->required()
                    ->default('Active'),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('country'),
                TextInput::make('pincode'),
                TextInput::make('emergency_contact_name'),
                TextInput::make('emergency_contact_relation'),
                TextInput::make('emergency_contact_phone')
                    ->tel(),
                TextInput::make('photo'),
                TextInput::make('government_id'),
                TextInput::make('pan'),
                TextInput::make('resume'),
                TextInput::make('offer_letter'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}
