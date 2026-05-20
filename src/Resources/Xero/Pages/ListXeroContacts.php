<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroContactResource;

class ListXeroContacts extends ListRecords
{
    protected static string $resource = XeroContactResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
