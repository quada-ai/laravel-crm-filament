<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;

class ViewDelivery extends ViewRecord
{
    use Concerns\HasDeliveryPortalAction;

    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->deliveryPortalAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
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
