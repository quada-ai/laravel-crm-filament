<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\LeadService;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(LeadService::class)->create(FormPayload::wrap($data));
        LeadResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
