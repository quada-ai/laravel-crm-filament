<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\Quote;

trait HasQuotePortalAction
{
    protected function quotePortalAction(): Action
    {
        return Action::make('previewPortal')
            ->label(__('laravel-crm-filament::labels.actions.preview_portal'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('primary')
            ->url(fn (Quote $record): string => URL::temporarySignedRoute(
                'laravel-crm.portal.quotes.show',
                now()->addDays(30),
                ['quote' => $record],
            ))
            ->openUrlInNewTab();
    }
}
