<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\CrmTeamResource;

class EditCrmTeam extends EditRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make(), Actions\ViewAction::make()];
    }
}
