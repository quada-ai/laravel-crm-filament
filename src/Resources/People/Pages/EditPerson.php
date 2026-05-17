<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Person $record */
        app(PersonService::class)->update($record, FormPayload::wrap($data));

        return $record->refresh();
    }
}

