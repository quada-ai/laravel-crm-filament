<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

trait HasDealMarkLostAction
{
    protected function dealMarkLostAction(): Action
    {
        return DealResource::markLostAction();
    }
}
