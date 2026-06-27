<?php

namespace VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\AddressTypeResource;

class CreateAddressType extends CreateRecord
{
    protected static string $resource = AddressTypeResource::class;
}
