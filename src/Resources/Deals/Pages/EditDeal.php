<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditDeal extends EditRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return DealResource::loadCrmCustomFieldsInto($data, $this->record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Deal $record */
        app(DealService::class)->update(FormPayload::wrap($data), $record);
        DealResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }
}
