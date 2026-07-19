<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Services\CRM\QuotationWorkflowService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(QuotationWorkflowService::class)
            ->create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Draft quotation created successfully.')
            ->body(
                'Lead status will move to Proposal Sent only after the approved quotation is actually emailed.'
            );
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}
