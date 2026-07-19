<?php

declare(strict_types=1);

namespace App\Filament\Resources\Refunds\Tables;

use App\Models\Refund;
use App\Services\Finance\RefundService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refund_no')
                    ->label('Refund')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_no')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('payment.payment_no')
                    ->label('Payment')
                    ->searchable(),
                TextColumn::make('customer.display_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('refund_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('amount')->money('INR')->sortable(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authorize('download')
                    ->visible(
                        fn (Refund $record): bool =>
                            filled($record->refund_pdf),
                    )
                    ->url(
                        fn (Refund $record): string =>
                            route('finance.refunds.download', $record),
                    )
                    ->openUrlInNewTab(),
                Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->authorize('cancel')
                    ->visible(
                        fn (Refund $record): bool =>
                            $record->status === 'Processed',
                    )
                    ->schema([
                        Textarea::make('reason')->required()->rows(3),
                    ])
                    ->action(function (
                        Refund $record,
                        array $data,
                    ): void {
                        app(RefundService::class)->cancel(
                            $record,
                            (string) $data['reason'],
                        );

                        Notification::make()
                            ->title('Refund cancelled and ledger restored.')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
            ]);
    }
}
