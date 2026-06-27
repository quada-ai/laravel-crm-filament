<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\TimezoneResource;

class ListTimezones extends ListRecords
{
    protected static string $resource = TimezoneResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
