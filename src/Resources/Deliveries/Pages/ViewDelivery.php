<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;

class ViewDelivery extends ViewRecord
{
    use Concerns\HasDeliveryPortalAction;

    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->deliveryPortalAction(),
            Actions\EditAction::make(),
        ];
    }
}
