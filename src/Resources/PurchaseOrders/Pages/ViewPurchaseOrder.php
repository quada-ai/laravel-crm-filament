<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;

class ViewPurchaseOrder extends ViewRecord
{
    use Concerns\HasPurchaseOrderPortalAction;
    use Concerns\HasPurchaseOrderSendAction;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->purchaseOrderPortalAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            $this->purchaseOrderSendAction(),
            $this->purchaseOrderDownloadPdfAction(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
