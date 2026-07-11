<?php

namespace App\Filament\Resources\Quotations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Models\Quotation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_code')
                    ->searchable(),
                TextColumn::make('lead_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('meeting_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('assigned_employee_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quotation_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])


->recordActions([

    Action::make('approve')

        ->label('Approve')

        ->icon('heroicon-o-check-circle')

        ->color('success')

        ->requiresConfirmation()

        ->visible(fn (Quotation $record): bool => $record->status === 'Draft')

        ->action(function (Quotation $record): void {

            $record->update([

                'status' => 'Approved',

                'approved_at' => now(),

                'approved_by' => auth()->id(),

            ]);

            Notification::make()

                ->title('Quotation Approved')

                ->success()

                ->send();

        }),

    Action::make('receivePayment')

        ->label('Receive Payment')

        ->icon('heroicon-o-banknotes')

        ->color('warning')

        ->visible(fn (Quotation $record): bool => $record->status === 'Approved')

        ->url(fn (Quotation $record): string =>

            route('filament.admin.resources.payments.create', [

                'quotation' => $record->id,

            ])

        ),

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
