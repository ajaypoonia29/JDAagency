<?php

namespace App\Filament\Resources\Meetings\Schemas;

use App\Models\Lead;
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
use Filament\Schemas\Components\Utilities\Set;
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
                                    ->label('Meeting Code')
                                    ->default(
                                        fn (): string =>
                                            Meeting::nextMeetingCode()
                                    )
                                    ->readOnly()
                                    ->dehydrated(false),

                                Select::make('lead_id')
                                    ->label('Lead')
                                    ->relationship('lead', 'company_name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->default(
                                        fn (): ?int =>
                                            request()->integer('lead') ?: null
                                    )
                                    ->disabled(
                                        fn ($record): bool =>
                                            filled($record)
                                            || request()->has('lead')
                                    )
                                    ->dehydrated()
                                    ->afterStateUpdated(
                                        function (Set $set, $state): void {

                                            if (blank($state)) {
                                                $set('customer_id', null);
                                                $set('assigned_employee_id', null);
                                                $set('meeting_title', null);
                                                $set('business_address', null);
                                                $set('latitude', null);
                                                $set('longitude', null);
                                                $set('google_maps_link', null);

                                                return;
                                            }

                                            $lead = Lead::query()
                                                ->with('convertedCustomer')
                                                ->find($state);

                                            if (! $lead) {
                                                return;
                                            }

                                            $set(
                                                'customer_id',
                                                $lead->converted_customer_id
                                            );

                                            $set(
                                                'assigned_employee_id',
                                                $lead->assigned_employee_id
                                            );

                                            $set(
                                                'meeting_title',
                                                'Meeting with '
                                                . (
                                                    $lead->company_name
                                                    ?: $lead->contact_person
                                                )
                                            );

                                            $set(
                                                'business_address',
                                                $lead->business_address
                                            );

                                            $set(
                                                'latitude',
                                                $lead->latitude
                                            );

                                            $set(
                                                'longitude',
                                                $lead->longitude
                                            );

                                            $set(
                                                'google_maps_link',
                                                $lead->google_maps_link
                                            );

                                        }
                                    )
                                    ->required(),

                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'display_name')
                                    ->searchable()
                                    ->preload()
                                    ->default(function (): ?int {

                                        $leadId = request()->integer('lead');

                                        if (! $leadId) {
                                            return null;
                                        }

                                        return Lead::find($leadId)
                                            ?->converted_customer_id;

                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('assigned_employee_id')
                                    ->label('Assigned Employee')
                                    ->relationship(
                                        'assignedEmployee',
                                        'full_name'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(function (): ?int {

                                        $leadId = request()->integer('lead');

                                        if (! $leadId) {
                                            return null;
                                        }

                                        return Lead::find($leadId)
                                            ?->assigned_employee_id;

                                    }),

                                TextInput::make('meeting_title')
                                    ->label('Meeting Title')
                                    ->default(function (): ?string {

                                        $leadId = request()->integer('lead');

                                        if (! $leadId) {
                                            return null;
                                        }

                                        $lead = Lead::find($leadId);

                                        if (! $lead) {
                                            return null;
                                        }

                                        return 'Meeting with '
                                            . (
                                                $lead->company_name
                                                ?: $lead->contact_person
                                            );

                                    })
                                    ->required(),

                                Select::make('meeting_type')
                                    ->label('Meeting Type')
                                    ->options([
                                        'Office' => 'Office',
                                        'Client Site' => 'Client Site',
                                        'Online' => 'Online',
                                        'Phone' => 'Phone',
                                    ])
                                    ->default('Client Site')
                                    ->required(),

                                DatePicker::make('meeting_date')
                                    ->label('Meeting Date')
                                    ->required(),

                                TimePicker::make('meeting_time')
                                    ->label('Meeting Time')
                                    ->required(),

                                TextInput::make('expected_duration')
                                    ->label('Expected Duration')
                                    ->numeric()
                                    ->suffix('Minutes'),

                            ]),

                    ]),

                Section::make('Business Location')
                    ->schema([

                        Textarea::make('business_address')
                            ->label('Business Address')
                            ->default(function (): ?string {

                                $leadId = request()->integer('lead');

                                return $leadId
                                    ? Lead::find($leadId)?->business_address
                                    : null;

                            })
                            ->columnSpanFull()
                            ->readOnly(),

                        Grid::make(2)
                            ->schema([

                                TextInput::make('latitude')
                                    ->default(function () {

                                        $leadId = request()->integer('lead');

                                        return $leadId
                                            ? Lead::find($leadId)?->latitude
                                            : null;

                                    })
                                    ->readOnly(),

                                TextInput::make('longitude')
                                    ->default(function () {

                                        $leadId = request()->integer('lead');

                                        return $leadId
                                            ? Lead::find($leadId)?->longitude
                                            : null;

                                    })
                                    ->readOnly(),

                                TextInput::make('google_maps_link')
                                    ->label('Google Maps Link')
                                    ->default(function (): ?string {

                                        $leadId = request()->integer('lead');

                                        return $leadId
                                            ? Lead::find($leadId)
                                                ?->google_maps_link
                                            : null;

                                    })
                                    ->readOnly(),

                                FileUpload::make('business_photo')
                                    ->label('Business Photo')
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
                            ->label('Meeting Notes')
                            ->rows(5)
                            ->columnSpanFull(),

                    ]),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                    ]),

            ]);
    }
}