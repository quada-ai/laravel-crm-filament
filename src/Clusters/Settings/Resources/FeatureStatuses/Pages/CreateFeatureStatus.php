<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\FeatureStatusResource;

class CreateFeatureStatus extends CreateRecord
{
    protected static string $resource = FeatureStatusResource::class;
}
