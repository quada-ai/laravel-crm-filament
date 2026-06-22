<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;

class ListFeatures extends ListRecords
{
    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...FeatureResource::listKanbanToggleActions('list'),
            Actions\CreateAction::make(),
        ];
    }
}
