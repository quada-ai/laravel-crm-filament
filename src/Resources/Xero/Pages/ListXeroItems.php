<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroItemResource;

class ListXeroItems extends ListRecords
{
    protected static string $resource = XeroItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
