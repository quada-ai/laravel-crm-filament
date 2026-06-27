<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;
}
