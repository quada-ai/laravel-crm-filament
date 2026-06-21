<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkLostAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkWonAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealReopenAction;

class ViewDeal extends ViewRecord
{
    use HasDealMarkLostAction;
    use HasDealMarkWonAction;
    use HasDealReopenAction;

    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DealResource::backToIndexAction(),
            $this->dealMarkWonAction(),
            $this->dealMarkLostAction(),
            $this->dealReopenAction(),
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
