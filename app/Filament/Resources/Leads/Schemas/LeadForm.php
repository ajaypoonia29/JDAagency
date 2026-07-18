<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use App\Support\CRM\LeadAssignmentAccess;
use App\Support\CRM\LeadOptionCatalog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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

                                Select::make('designation')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::designations(
                                                $record?->designation,
                                            ),
                                    )
                                    ->searchable()
                                    ->native(false),

                                Select::make('industry')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::industries(
                                                $record?->industry,
                                            ),
                                    )
                                    ->searchable()
                                    ->native(false),

                                Select::make('business_type')
                                    ->label('Business Type')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::businessTypes(
                                                $record?->business_type,
                                            ),
                                    )
                                    ->searchable()
                                    ->native(false),

                                Select::make('company_size')
                                    ->label('Company Size')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::companySizes(
                                                $record?->company_size,
                                            ),
                                    )
                                    ->native(false),

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
                                    ->label('Sales Executive')
                                    ->default(
                                        fn (): ?int =>
                                            LeadAssignmentAccess::currentActiveEmployeeId(
                                                auth()->user(),
                                            ),
                                    )
                                    ->disabled(
                                        fn (): bool =>
                                            ! LeadAssignmentAccess::canManage(
                                                auth()->user(),
                                            ),
                                    )
                                    ->relationship(
                                        name: 'assignedEmployee',
                                        titleAttribute: 'full_name',
                                        modifyQueryUsing:
                                            fn (Builder $query): Builder =>
                                                LeadAssignmentAccess::scopeEmployeeOptions(
                                                    $query,
                                                    auth()->user(),
                                                ),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(
                                        fn (): bool =>
                                            ! LeadAssignmentAccess::canManage(
                                                auth()->user(),
                                            ),
                                    )
                                    ->helperText(
                                        fn (): string =>
                                            LeadAssignmentAccess::helpText(
                                                auth()->user(),
                                            ),
                                    ),

                                Select::make('lead_source')
                                    ->label('Lead Source')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::leadSources(
                                                $record?->lead_source,
                                            ),
                                    )
                                    ->searchable()
                                    ->native(false),

                                Select::make('estimated_value')
                                    ->label('Estimated Value')
                                    ->options(
                                        fn (?Lead $record): array =>
                                            LeadOptionCatalog::estimatedValues(
                                                $record?->estimated_value,
                                            ),
                                    )
                                    ->formatStateUsing(
                                        static fn (
                                            float|int|string|null $state,
                                        ): ?string =>
                                            $state === null
                                            || $state === ''
                                                ? null
                                                : rtrim(
                                                    rtrim(
                                                        number_format(
                                                            (float) $state,
                                                            2,
                                                            '.',
                                                            '',
                                                        ),
                                                        '0',
                                                    ),
                                                    '.',
                                                ),
                                    )
                                    ->default('0')
                                    ->native(false),

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