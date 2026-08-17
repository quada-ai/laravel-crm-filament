<?php

namespace VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\LeadStatusResource;

class CreateLeadStatus extends CreateRecord
{
    protected static string $resource = LeadStatusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Core LeadStatus has no observer to stamp external_id — set it ourselves.
        $data['external_id'] ??= (string) Uuid::uuid4();

        if ($tenant = Filament::getTenant()) {
            $model = static::getModel();
            $table = (new $model)->getTable();
            if (Schema::hasColumn($table, 'tenant_id')) {
                $data['tenant_id'] ??= $tenant->getKey();
            } elseif (Schema::hasColumn($table, 'team_id')) {
                $data['team_id'] ??= $tenant->getKey();
            }
        }

        return $data;
    }
}
