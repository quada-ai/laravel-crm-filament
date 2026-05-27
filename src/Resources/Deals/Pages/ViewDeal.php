<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
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
            $this->dealMarkWonAction(),
            $this->dealMarkLostAction(),
            $this->dealReopenAction(),
            Actions\EditAction::make(),
        ];
    }
}
