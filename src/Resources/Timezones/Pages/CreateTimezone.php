<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\TimezoneResource;

class CreateTimezone extends CreateRecord
{
    protected static string $resource = TimezoneResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
