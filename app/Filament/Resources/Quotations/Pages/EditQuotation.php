<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Services\QuotationPdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->authorize('download')
                ->action(function (QuotationPdfService $pdf) {

                    return response()->streamDownload(

                        fn () => print(
                            $pdf
                                ->generate($this->record)
                                ->output()
                        ),

                        $this->record->quotation_code . '.pdf'
                    );
                }),

            DeleteAction::make(),

            ForceDeleteAction::make(),

            RestoreAction::make(),

        ];
    }
}