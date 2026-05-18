<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
