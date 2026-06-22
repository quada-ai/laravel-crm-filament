<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\MonitorService;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource;

class CreateMonitor extends CreateRecord
{
    protected static string $resource = MonitorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(MonitorService::class)->create($data);
    }
}
