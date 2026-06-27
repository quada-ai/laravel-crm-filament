<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;

class ListLabels extends ListRecords
{
    protected static string $resource = LabelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
