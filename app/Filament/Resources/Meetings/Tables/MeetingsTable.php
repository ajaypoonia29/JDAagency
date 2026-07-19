<?php

namespace App\Filament\Resources\Meetings\Tables;

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

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('meeting_code')
                    ->label('Meeting')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meeting_title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lead.company_name')
                    ->label('Lead')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('assignedEmployee.full_name')
                    ->label('Sales Executive')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('meeting_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('meeting_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('meeting_time')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('outcome')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('meeting_type')
                    ->options([
                        'Office' => 'Office',
                        'Client Site' => 'Client Site',
                        'Online' => 'Online',
                        'Phone' => 'Phone',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'Scheduled' => 'Scheduled',
                        'Confirmed' => 'Confirmed',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                        'Rescheduled' => 'Rescheduled',
                        'No Show' => 'No Show',
                    ]),

                SelectFilter::make('outcome')
                    ->options([
                        'Pending' => 'Pending',
                        'Interested' => 'Interested',
                        'Not Interested' => 'Not Interested',
                        'Follow-up Required' => 'Follow-up Required',
                        'Quotation Required' => 'Quotation Required',
                        'Converted' => 'Converted',
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