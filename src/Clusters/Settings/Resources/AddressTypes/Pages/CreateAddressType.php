<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\AddressTypeResource;

class CreateAddressType extends CreateRecord
{
    protected static string $resource = AddressTypeResource::class;
}
