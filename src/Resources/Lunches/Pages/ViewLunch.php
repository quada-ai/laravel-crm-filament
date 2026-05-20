<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Lunches\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\LunchResource;

class ViewLunch extends ViewRecord
{
    protected static string $resource = LunchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
