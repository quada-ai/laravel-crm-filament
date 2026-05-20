<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ContactTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ContactTypes\ContactTypeResource;

class CreateContactType extends CreateRecord
{
    protected static string $resource = ContactTypeResource::class;
}
