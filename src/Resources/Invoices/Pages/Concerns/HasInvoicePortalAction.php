<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Facades\URL;
use VentureDrake\LaravelCrm\Models\Invoice;

trait HasInvoicePortalAction
{
    protected function invoicePortalAction(): Action
    {
        return Action::make('previewPortal')
            ->label(__('laravel-crm-filament::labels.actions.preview_portal'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('primary')
            ->url(fn (Invoice $record): string => URL::temporarySignedRoute(
                'laravel-crm.portal.invoices.show',
                now()->addDays(30),
                ['invoice' => $record],
            ))
            ->openUrlInNewTab();
    }
}
