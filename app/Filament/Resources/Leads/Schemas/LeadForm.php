<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Employee;
use App\Models\Lead;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Lead Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('lead_code')
                                    ->default(fn () => Lead::nextLeadCode())
                                    ->readOnly()
                                    ->required(),

                                Select::make('lead_status')
                                    ->options([
                                        'New' => 'New',
                                        'Contacted' => 'Contacted',
                                        'Qualified' => 'Qualified',
                                        'Meeting Scheduled' => 'Meeting Scheduled',
                                        'Proposal Sent' => 'Proposal Sent',
                                        'Negotiation' => 'Negotiation',
                                        'Won' => 'Won',
                                        'Lost' => 'Lost',
                                    ])
                                    ->default('New')
                                    ->required(),

                                Select::make('priority')
                                    ->options([
                                        'Low' => 'Low',
                                        'Medium' => 'Medium',
                                        'High' => 'High',
                                        'Urgent' => 'Urgent',
                                    ])
                                    ->default('Medium')
                                    ->required(),

                            ]),
                    ]),

                Section::make('Company Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('company_name')
                                    ->maxLength(255),

                                TextInput::make('contact_person')
                                    ->required(),

                                TextInput::make('designation'),

                                TextInput::make('industry'),

                                TextInput::make('business_type'),

                                TextInput::make('company_size'),

                            ]),
                    ]),

                Section::make('Contact Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('email')
                                    ->email()
                                    ->required(),

                                TextInput::make('phone')
                                    ->tel()
                                    ->required(),

                                TextInput::make('whatsapp')
                                    ->tel(),

                                TextInput::make('website')
                                    ->url(),

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

                                TextInput::make('estimated_value')
                                    ->numeric()
                                    ->default(0),

                                DatePicker::make('expected_closing_date'),

                                DatePicker::make('next_follow_up_date'),

                            ]),
                    ]),

                Section::make('Requirements')
                    ->schema([

                        Textarea::make('requirements_summary')
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('internal_notes')
                            ->rows(5)
                            ->columnSpanFull(),

                    ]),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->default(true),

                    ]),

            ]);
    }
}