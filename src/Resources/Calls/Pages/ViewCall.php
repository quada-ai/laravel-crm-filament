<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Calls\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Calls\CallResource;

class ViewCall extends ViewRecord
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
