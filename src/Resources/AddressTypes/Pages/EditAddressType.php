<?php

namespace VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\AddressTypeResource;

class EditAddressType extends EditRecord
{
    protected static string $resource = AddressTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
