<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\TimezoneResource;

class CreateTimezone extends CreateRecord
{
    protected static string $resource = TimezoneResource::class;
}
