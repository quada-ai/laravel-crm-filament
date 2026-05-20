<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\CrmTeamResource;

class ViewCrmTeam extends ViewRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
