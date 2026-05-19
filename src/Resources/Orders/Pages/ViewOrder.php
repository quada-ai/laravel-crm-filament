<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    use Concerns\HasOrderConvertToDeliveryAction;
    use Concerns\HasOrderConvertToInvoiceAction;
    use Concerns\HasOrderConvertToPurchaseOrderAction;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            $this->orderConvertToInvoiceAction(),
            $this->orderConvertToDeliveryAction(),
            $this->orderConvertToPurchaseOrderAction(),
        ];
    }
}
