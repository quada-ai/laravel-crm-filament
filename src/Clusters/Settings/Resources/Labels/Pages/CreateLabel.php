<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\LabelResource;

class CreateLabel extends CreateRecord
{
    protected static string $resource = LabelResource::class;
}
