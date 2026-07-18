<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Services\CRM\LeadWorkflowService;
use App\Support\CRM\LeadAssignmentAccess;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(
        array $data,
    ): array {
        return LeadAssignmentAccess::enforceWriteAssignment(
            $data,
            auth()->user(),
        );
    }

    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        /** @var Lead $record */
        return app(LeadWorkflowService::class)
            ->update($record, $data);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lead updated successfully.')
            ->body(
                'Customer '
                . $this->record->convertedCustomer?->customer_code
                . ' remains linked without overwriting existing customer data.'
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
