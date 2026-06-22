<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\FeatureStatusResource;

class ListFeatureStatuses extends ListRecords
{
    protected static string $resource = FeatureStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
