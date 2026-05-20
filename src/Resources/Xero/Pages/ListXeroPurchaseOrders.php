<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroPurchaseOrderResource;

class ListXeroPurchaseOrders extends ListRecords
{
    protected static string $resource = XeroPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
