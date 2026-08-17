<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Schema;
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
