<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrm\Models\Quote;

trait HasQuotePortalAction
{
    protected function quotePortalAction(): Action
    {
        return Action::make('portal')
            ->label('Open portal')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn (Quote $record): string => url('p/quotes/' . $record->external_id))
            ->openUrlInNewTab();
    }
}
