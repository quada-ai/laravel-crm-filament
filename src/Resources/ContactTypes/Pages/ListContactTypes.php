<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\ContactTypeResource;

class ListContactTypes extends ListRecords
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
