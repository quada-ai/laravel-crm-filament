<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;

class ViewPurchaseOrder extends ViewRecord
{
    use Concerns\HasPurchaseOrderPortalAction;
    use Concerns\HasPurchaseOrderSendAction;
    use HasXeroSyncStateInfolist;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->purchaseOrderPortalAction(),
            Actions\EditAction::make(),
            $this->purchaseOrderSendAction(),
            $this->purchaseOrderDownloadPdfAction(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            static::xeroSyncStateSection(fn (PurchaseOrder $po) => $po->xeroPurchaseOrder),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
            $this->getInfolistContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
