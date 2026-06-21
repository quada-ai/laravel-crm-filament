<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    use Concerns\HasOrderConvertToDeliveryAction;
    use Concerns\HasOrderConvertToInvoiceAction;
    use Concerns\HasOrderConvertToPurchaseOrderAction;
    use Concerns\HasOrderPortalAction;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->orderPortalAction()
                ->button()
                ->hiddenLabel(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            $this->orderConvertToInvoiceAction(),
            $this->orderConvertToDeliveryAction(),
            $this->orderConvertToPurchaseOrderAction(),
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
