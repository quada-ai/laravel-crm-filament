<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\ContactTypeResource;

class CreateContactType extends CreateRecord
{
    protected static string $resource = ContactTypeResource::class;
}
