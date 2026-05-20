<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\CrmTeamResource;

class CreateCrmTeam extends CreateRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Team uses Spatie Permission's team_id == user_id authoring convention.
        $data['user_id'] ??= auth()->id();

        return $data;
    }
}
