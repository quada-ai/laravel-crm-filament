<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    use Concerns\HasOrderConvertToDeliveryAction;
    use Concerns\HasOrderConvertToInvoiceAction;
    use Concerns\HasOrderConvertToPurchaseOrderAction;
    use Concerns\HasOrderPortalAction;
    use HasXeroSyncStateInfolist;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->orderPortalAction(),
            Actions\EditAction::make(),
            $this->orderConvertToInvoiceAction(),
            $this->orderConvertToDeliveryAction(),
            $this->orderConvertToPurchaseOrderAction(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            static::xeroSyncStateSection(function (Order $order) {
                // Orders aren't mirrored to Xero directly — surface the
                // sync state of the first linked invoice (if any).
                $invoice = $order->invoices()->latest()->first();

                return $invoice?->xeroInvoice;
            }),
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
