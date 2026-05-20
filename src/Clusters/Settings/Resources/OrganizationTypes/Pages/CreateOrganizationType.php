<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\OrganizationTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\OrganizationTypes\OrganizationTypeResource;

class CreateOrganizationType extends CreateRecord
{
    protected static string $resource = OrganizationTypeResource::class;
}
