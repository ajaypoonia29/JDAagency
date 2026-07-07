<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('customer_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->searchable(),

                TextColumn::make('primary_email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('primary_phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('assignedEmployee.full_name')
                    ->label('Sales Executive')
                    ->sortable(),

                TextColumn::make('customer_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('customer_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lead' => 'gray',
                        'Prospect' => 'warning',
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        'Blacklisted' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('credit_limit')
                    ->money('INR')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
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