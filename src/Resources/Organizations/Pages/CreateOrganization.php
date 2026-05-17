<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(OrganizationService::class)->create(FormPayload::wrap($data));
        OrganizationResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}

