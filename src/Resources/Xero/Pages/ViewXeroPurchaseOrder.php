<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroPurchaseOrderResource;

class ViewXeroPurchaseOrder extends ViewRecord
{
    protected static string $resource = XeroPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
