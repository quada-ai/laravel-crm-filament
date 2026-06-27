<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Roles\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\Roles\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
}
