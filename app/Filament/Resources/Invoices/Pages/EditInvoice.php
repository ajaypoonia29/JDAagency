<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\Finance\InvoiceService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        /** @var Invoice $record */
        return app(InvoiceService::class)->update($record, $data);
    }
}
