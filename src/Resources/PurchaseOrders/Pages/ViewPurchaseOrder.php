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
            PurchaseOrderResource::backToIndexAction(),
            $this->purchaseOrderSendAction()
                ->button()
                ->label(__('laravel-crm-filament::labels.actions.send'))
                ->color('gray'),
            $this->purchaseOrderDownloadPdfAction()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-down-tray'),
            $this->purchaseOrderPortalAction()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray'),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
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
