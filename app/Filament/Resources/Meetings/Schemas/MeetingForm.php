<?php

namespace App\Filament\Resources\Meetings\Schemas;

use App\Models\Meeting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Meeting Information')
                    ->schema([

                        Grid::make(3)
                            ->schema([

                                TextInput::make('meeting_code')
                                    ->default(fn () => Meeting::nextMeetingCode())
                                    ->readOnly()
                                    ->required(),

                                Select::make('lead_id')
                                    ->relationship('lead', 'company_name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('customer_id')
                                    ->relationship('customer', 'display_name')
                                    ->searchable()
                                    ->preload(),

                                Select::make('assigned_employee_id')
                                    ->relationship('assignedEmployee', 'full_name')
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('meeting_title')
                                    ->required(),

                                Select::make('meeting_type')
                                    ->options([
                                        'Office' => 'Office',
                                        'Client Site' => 'Client Site',
                                        'Online' => 'Online',
                                        'Phone' => 'Phone',
                                    ])
                                    ->default('Client Site')
                                    ->required(),

                                DatePicker::make('meeting_date')
                                    ->required(),

                                TimePicker::make('meeting_time')
                                    ->required(),

                                TextInput::make('expected_duration')
                                    ->numeric()
                                    ->suffix('Minutes'),

                            ]),
                    ]),

                Section::make('Business Location')
                    ->schema([

                        Textarea::make('business_address')
                            ->columnSpanFull()
                            ->readOnly(),

                        Grid::make(2)
                            ->schema([

                                TextInput::make('latitude')
                                    ->readOnly(),

                                TextInput::make('longitude')
                                    ->readOnly(),

                                TextInput::make('google_maps_link')
                                    ->readOnly(),

                                FileUpload::make('business_photo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('meetings/business'),

                            ]),
                    ]),

                Section::make('Meeting Result')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                Select::make('status')
                                    ->options([
                                        'Scheduled' => 'Scheduled',
                                        'Confirmed' => 'Confirmed',
                                        'Completed' => 'Completed',
                                        'Cancelled' => 'Cancelled',
                                        'Rescheduled' => 'Rescheduled',
                                        'No Show' => 'No Show',
                                    ])
                                    ->default('Scheduled')
                                    ->required(),

                                Select::make('outcome')
                                    ->options([
                                        'Pending' => 'Pending',
                                        'Interested' => 'Interested',
                                        'Not Interested' => 'Not Interested',
                                        'Follow-up Required' => 'Follow-up Required',
                                        'Quotation Required' => 'Quotation Required',
                                        'Converted' => 'Converted',
                                    ])
                                    ->default('Pending')
                                    ->required(),

                            ]),

                        Textarea::make('meeting_notes')
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