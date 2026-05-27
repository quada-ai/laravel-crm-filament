<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

trait HasDealMarkWonAction
{
    protected function dealMarkWonAction(): Action
    {
        return DealResource::markWonAction();
    }
}
