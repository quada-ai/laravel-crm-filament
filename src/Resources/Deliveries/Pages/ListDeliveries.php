<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;

class ListDeliveries extends ListRecords
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
