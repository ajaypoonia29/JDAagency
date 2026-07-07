<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basic Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('employee_code')
    ->required()
    ->maxLength(50)
    ->default(fn () => \App\Models\Employee::nextEmployeeCode())
    ->readOnly(),

                                TextInput::make('full_name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->email()
                                    ->required(),

                                TextInput::make('phone')
                                    ->tel()
                                    ->required(),

                                TextInput::make('alternate_phone')
                                    ->tel(),

                                FileUpload::make('photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('employees/photos'),

                            ]),

                    ]),

                Section::make('Employment')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('designation_id')
                                    ->relationship('designation', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                DatePicker::make('joining_date'),

                                Select::make('employment_status')
                                    ->options([
                                        'Active' => 'Active',
                                        'Inactive' => 'Inactive',
                                        'On Leave' => 'On Leave',
                                        'Resigned' => 'Resigned',
                                    ])
                                    ->required(),

                                TextInput::make('salary')
                                    ->numeric(),

                            ]),

                    ]),

                Section::make('Personal Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                DatePicker::make('date_of_birth'),

                                TextInput::make('gender'),

                                TextInput::make('blood_group'),

                            ]),

                    ]),

                Section::make('Address')
                    ->schema([

                        Textarea::make('address')
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([

                                TextInput::make('city'),

                                TextInput::make('state'),

                                TextInput::make('country'),

                                TextInput::make('pincode'),

                            ]),

                    ]),

                Section::make('Emergency Contact')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('emergency_contact_name'),

                                TextInput::make('emergency_contact_relation'),

                                TextInput::make('emergency_contact_phone')
                                    ->tel(),

                            ]),

                    ]),

                Section::make('Documents')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('government_id'),

                                TextInput::make('aadhaar_number'),

                                TextInput::make('pan'),

                                TextInput::make('google_drive_folder'),

                                FileUpload::make('resume')
                                    ->disk('public')
                                    ->directory('employees/resume'),

                                FileUpload::make('offer_letter')
                                    ->disk('public')
                                    ->directory('employees/offer-letters'),

                            ]),

                    ]),

                Section::make('Notes & Status')
                    ->schema([

                        Textarea::make('notes')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true),

                    ]),
            ]);
    }
}