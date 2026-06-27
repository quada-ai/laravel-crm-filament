<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\FeatureStatusResource;

class CreateFeatureStatus extends CreateRecord
{
    protected static string $resource = FeatureStatusResource::class;
}
