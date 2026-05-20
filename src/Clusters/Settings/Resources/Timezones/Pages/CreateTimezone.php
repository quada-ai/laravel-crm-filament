<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Timezones\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Timezones\TimezoneResource;

class CreateTimezone extends CreateRecord
{
    protected static string $resource = TimezoneResource::class;
}
