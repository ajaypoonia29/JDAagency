<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Quotation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingQuotationsWidget extends TableWidget
{
    protected static ?string $heading = 'Pending Quotations';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quotation::query()
                    ->whereIn('status', [
                        'Draft',
                        'Sent',
                    ])
                    ->latest('quotation_date')
            )

            ->columns([

                Tables\Columns\TextColumn::make('quotation_code')
                    ->label('Quote #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->placeholder('Lead'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Draft',
                        'primary' => 'Sent',
                    ]),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Date')
                    ->date(),

            ])

            ->paginated(false)
->searchable(false);
    }
}