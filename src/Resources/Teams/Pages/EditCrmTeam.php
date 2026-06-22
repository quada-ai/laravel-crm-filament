<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;

class EditCrmTeam extends EditRecord
{
    protected static string $resource = CrmTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make(), Actions\ViewAction::make()];
    }
}
