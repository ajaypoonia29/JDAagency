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
use App\Services\Communication\EmailService;

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

	TextColumn::make('quotation_sent_at')
    	->label('Last Emailed')
    	->since()
    	->sortable()
    	->toggleable(),

	TextColumn::make('quotation_send_count')
    	->label('Emails')
    	->badge()
    	->sortable(),

	TextColumn::make('last_sent_to')
    	->label('Last Recipient')
    	->searchable()
    	->toggleable(isToggledHiddenByDefault: true),
                
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

        ->visible(fn (Quotation $record): bool =>

    in_array($record->status, ['Approved', 'Completed'])

    && $record->payment_status !== 'Paid'

)

        ->url(fn (Quotation $record): string =>

            route('filament.admin.resources.payments.create', [

                'quotation' => $record->id,

            ])

        ),

Action::make('sendQuotation')

    ->label(fn (Quotation $record): string =>
        $record->quotation_send_count > 0
            ? 'Resend Email'
            : 'Send Email'
    )

    ->icon('heroicon-o-envelope')

    ->color('info')

    ->requiresConfirmation()

    ->modalDescription(fn (Quotation $record): string =>
        'Send quotation to: ' . ($record->customer?->primary_email ?? 'No email available')
    )

    ->action(function (Quotation $record): void {

        if (EmailService::sendQuotation($record)) {

            $record->refresh();

            Notification::make()
                ->title('Quotation emailed successfully.')
                ->body(
                    'Total sends: ' . $record->quotation_send_count
                )
                ->success()
                ->send();

        } else {

            Notification::make()
                ->title('Unable to send quotation email.')
                ->danger()
                ->send();

        }

    }),


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
