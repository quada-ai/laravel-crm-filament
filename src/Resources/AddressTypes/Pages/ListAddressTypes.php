<?php

namespace VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\AddressTypeResource;

class ListAddressTypes extends ListRecords
{
    protected static string $resource = AddressTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
