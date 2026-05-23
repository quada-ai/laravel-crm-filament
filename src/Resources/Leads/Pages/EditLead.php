<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::convertAction(),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return LeadResource::loadCrmCustomFieldsInto($data, $this->record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Lead $record */
        app(LeadService::class)->update(FormPayload::wrap($data), $record);
        LeadResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }
}
