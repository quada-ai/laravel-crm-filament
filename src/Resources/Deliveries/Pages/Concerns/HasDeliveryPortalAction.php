<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrm\Models\Delivery;

trait HasDeliveryPortalAction
{
    protected function deliveryPortalAction(): Action
    {
        return Action::make('previewPortal')
            ->label(__('laravel-crm-filament::labels.actions.preview_portal'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('primary')
            ->visible(fn (Delivery $record): bool => $record->external_id !== null && $record->deliveryProducts()->count() > 0)
            ->url(fn (Delivery $record): string => url('p/deliveries/' . $record->external_id))
            ->openUrlInNewTab();
    }
}
