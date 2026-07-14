<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Services\CRM\LeadWorkflowService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LeadWorkflowService::class)
            ->create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lead and customer created successfully.')
            ->body(
                'Customer '
                . $this->record->convertedCustomer?->customer_code
                . ' is safely linked to this lead.'
            );
    }
}
