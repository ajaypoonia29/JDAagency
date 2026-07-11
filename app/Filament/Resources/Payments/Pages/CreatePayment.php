<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Finance\PaymentService;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['payment_no'] = Payment::nextPaymentNumber();

        $data['receipt_number'] = Payment::nextReceiptNumber();

        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $quotationId = request()->integer('quotation');

        if (! $quotationId) {
            return;
        }

        $quotation = Quotation::find($quotationId);

        if (! $quotation) {
            return;
        }

        $this->form->fill([

            'quotation_id' => $quotation->id,

            'customer_id' => $quotation->customer_id,

            'amount' => $quotation->balance_due > 0
    ? $quotation->balance_due
    : $quotation->grand_total,

        ]);
    }

    protected function afterCreate(): void
{
    PaymentService::process($this->record);
}

}