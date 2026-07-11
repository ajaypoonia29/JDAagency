<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCustomersWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Customers';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->latest()
                    ->limit(10)
            )

            ->columns([

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Phone'),

                Tables\Columns\TextColumn::make('assignedEmployee.full_name')
                    ->label('Assigned To')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),

            ])

            ->paginated(false)
->searchable(false);
    }
}