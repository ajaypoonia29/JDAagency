<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Meeting;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodaysMeetingsWidget extends TableWidget
{
    protected static ?string $heading = "Today's Sales Agenda";

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Meeting::query()
                    ->whereDate('meeting_date', today())
                    ->orderBy('meeting_time')
            )

            ->columns([

                Tables\Columns\TextColumn::make('meeting_time')
                    ->label('Time')
                    ->time('h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('meeting_title')
                    ->label('Meeting')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->placeholder('Lead'),

                Tables\Columns\BadgeColumn::make('meeting_type')
                    ->colors([
                        'primary',
                    ]),

                Tables\Columns\BadgeColumn::make('status'),

            ])

            ->paginated(false)
->searchable(false);

    }
}