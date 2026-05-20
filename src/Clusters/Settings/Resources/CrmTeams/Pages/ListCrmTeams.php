<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\CrmTeamResource;

class ListCrmTeams extends ListRecords
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
