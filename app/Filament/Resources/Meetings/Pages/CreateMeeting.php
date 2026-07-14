<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Meetings\MeetingResource;
use App\Services\CRM\MeetingWorkflowService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(MeetingWorkflowService::class)
            ->create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Meeting scheduled successfully.')
            ->body(
                'Lead '
                . $this->record->lead?->lead_code
                . ' is now in the meeting workflow.'
            );
    }

    protected function getRedirectUrl(): string
    {
        $lead = $this->record->lead;

        if ($lead) {
            return LeadResource::getUrl('edit', [
                'record' => $lead,
            ]);
        }

        return static::$resource::getUrl('index');
    }
}
