<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\LeadStatusResource;

class CreateLeadStatus extends CreateRecord
{
    protected static string $resource = LeadStatusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Core LeadStatus has no observer to stamp external_id — set it ourselves.
        $data['external_id'] ??= (string) Uuid::uuid4();

        return $data;
    }
}
