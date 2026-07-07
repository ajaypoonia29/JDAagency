<?php

namespace App\Filament\Resources\Leads\Tables;

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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->searchable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('assignedEmployee.full_name')
                    ->label('Sales Executive')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lead_status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),

                TextColumn::make('estimated_value')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('next_follow_up_date')
                    ->date()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('lead_status')
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

                TernaryFilter::make('is_active'),

                TrashedFilter::make(),

            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make(),

                    ForceDeleteBulkAction::make(),

                    RestoreBulkAction::make(),

                ]),

            ]);
    }
}