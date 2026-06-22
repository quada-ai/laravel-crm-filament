<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;

class ViewCrmTeam extends ViewRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
