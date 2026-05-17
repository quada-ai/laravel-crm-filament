<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\FieldGroupResource;

class ListFieldGroups extends ListRecords
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
