<?php

namespace VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\OrganizationTypeResource;

class EditOrganizationType extends EditRecord
{
    protected static string $resource = OrganizationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
