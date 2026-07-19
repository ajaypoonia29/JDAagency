<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('lead_code')
                    ->label('Lead')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->placeholder('Individual Lead')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('convertedCustomer.display_name')
                    ->label('Customer')
                    ->placeholder('Not linked')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignedEmployee.full_name')
                    ->label('Sales Executive')
                    ->placeholder('Not assigned')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lead_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),

                TextColumn::make('estimated_value')
                    ->label('Estimated Value')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('next_follow_up_date')
                    ->label('Next Follow-up')
                    ->date()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('lead_status')
                    ->label('Lead Status')
                    ->options([
                        'New' => 'New',
                        'Contacted' => 'Contacted',
                        'Qualified' => 'Qualified',
                        'Meeting Scheduled' => 'Meeting Scheduled',
                        'Proposal Sent' => 'Proposal Sent',
                        'Negotiation' => 'Negotiation',
                        'Won' => 'Won',
                        'Lost' => 'Lost',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        'Low' => 'Low',
                        'Medium' => 'Medium',
                        'High' => 'High',
                        'Urgent' => 'Urgent',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active'),

                TrashedFilter::make(),

            ])

            ->recordActions([

                Action::make('scheduleMeeting')
                    ->label('Schedule Meeting')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(
                        fn (Lead $record): bool =>
                            auth()->user()?->can(
                                'scheduleMeeting',
                                $record,
                            ) === true
                    )
                    ->url(
                        fn (Lead $record): string =>
                            route(
                                'filament.admin.resources.meetings.create',
                                [
                                    'lead' => $record->id,
                                ]
                            )
                    ),

                EditAction::make(),

            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(),

                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords(),

                    RestoreBulkAction::make()
                        ->authorizeIndividualRecords(),

                ]),

            ]);
    }
}