<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

/**
 * Exposes both the "unaccept" and "unreject" header actions.
 * Each individual Filament Action is responsible for its own
 * visibility — the unaccept variant is shown only when
 * `accepted_at` is set, the unreject variant only when
 * `rejected_at` is set. Returning the pair as an array lets
 * Filament evaluate each gate per-render against the bound
 * record, so only the contextually-correct action surfaces.
 *
 * @method array quoteUnacceptActions()
 */
trait HasQuoteUnacceptAction
{
    /**
     * @return array<int, Action>
     */
    protected function quoteUnacceptActions(): array
    {
        return [
            QuoteResource::unacceptAction(),
            QuoteResource::unrejectAction(),
        ];
    }
}
