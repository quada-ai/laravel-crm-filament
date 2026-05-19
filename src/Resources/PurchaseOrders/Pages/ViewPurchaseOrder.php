<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;

class ViewPurchaseOrder extends ViewRecord
{
    use Concerns\HasPurchaseOrderSendAction;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            $this->purchaseOrderSendAction(),
            $this->purchaseOrderDownloadPdfAction(),
        ];
    }
}
