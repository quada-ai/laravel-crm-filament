<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\FieldGroupResource;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;
}
