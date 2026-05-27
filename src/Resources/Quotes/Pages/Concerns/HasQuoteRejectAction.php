<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

trait HasQuoteRejectAction
{
    protected function quoteRejectAction(): Action
    {
        return QuoteResource::rejectAction();
    }
}
