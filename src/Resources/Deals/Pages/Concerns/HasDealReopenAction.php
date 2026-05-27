<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

trait HasDealReopenAction
{
    protected function dealReopenAction(): Action
    {
        return DealResource::reopenAction();
    }
}
