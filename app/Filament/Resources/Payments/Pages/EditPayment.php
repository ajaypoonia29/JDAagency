<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\Finance\PaymentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PaymentService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(
                    fn (Payment $record): bool =>
                        app(PaymentService::class)->delete($record)
                ),

            ForceDeleteAction::make()
                ->using(
                    fn (Payment $record): bool =>
                        app(PaymentService::class)->forceDelete($record)
                ),

            RestoreAction::make()
                ->using(
                    fn (Payment $record): bool =>
                        app(PaymentService::class)->restore($record)
                ),
        ];
    }
}
