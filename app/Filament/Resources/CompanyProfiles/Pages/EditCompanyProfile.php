<?php

namespace App\Filament\Resources\CompanyProfiles\Pages;

use App\Filament\Resources\CompanyProfiles\CompanyProfileResource;
use App\Services\Communication\MailTestService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompanyProfile extends EditRecord
{
    protected static string $resource = CompanyProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('testEmail')

                ->label('Send Test Email')

                ->icon('heroicon-o-envelope')

                ->color('success')

                ->requiresConfirmation()

                ->action(function () {

                    $company = $this->record;

                    if (blank($company->test_email)) {

                        Notification::make()
                            ->title('Test email address is not configured.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $result = MailTestService::send(
                        $company->test_email
                    );

                    Notification::make()
                        ->title($result['message'])
                        ->color(
                            $result['success']
                                ? 'success'
                                : 'danger'
                        )
                        ->send();

                }),

            DeleteAction::make(),

        ];
    }
}