<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;

class CreateLabel extends CreateRecord
{
    protected static string $resource = LabelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Core Label has no observer to stamp external_id — set it ourselves.
        // Panel-driven creates fail on the crm_labels.external_id NOT NULL constraint without this.
        $data['external_id'] ??= (string) Uuid::uuid4();

        return $data;
    }
}
