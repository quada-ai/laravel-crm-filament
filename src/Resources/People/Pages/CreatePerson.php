<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(PersonService::class)->create(FormPayload::wrap($data));
        if (isset($data['labels'])) {
            $record->labels()->sync($data['labels']);
        }
        PersonResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
