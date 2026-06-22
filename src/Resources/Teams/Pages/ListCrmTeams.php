<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;

class ListCrmTeams extends ListRecords
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
