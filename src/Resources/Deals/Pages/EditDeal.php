<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkLostAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkWonAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealReopenAction;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditDeal extends EditRecord
{
    use HasDealMarkLostAction;
    use HasDealMarkWonAction;
    use HasDealReopenAction;

    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->dealMarkWonAction(),
            $this->dealMarkLostAction(),
            $this->dealReopenAction(),
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
