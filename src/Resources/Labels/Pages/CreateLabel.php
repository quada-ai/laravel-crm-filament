<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;

class CreateLabel extends CreateRecord
{
    protected static string $resource = LabelResource::class;
}
