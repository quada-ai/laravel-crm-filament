<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant()?->id : null;
        if ($tenantId) {
            $data['tenant_id'] = $tenantId;
            $data['team_id'] = $tenantId;
        }

        return $data;
    }
}
