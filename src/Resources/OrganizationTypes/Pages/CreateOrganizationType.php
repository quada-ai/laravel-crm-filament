<?php

namespace VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\OrganizationTypeResource;

class CreateOrganizationType extends CreateRecord
{
    protected static string $resource = OrganizationTypeResource::class;
}
