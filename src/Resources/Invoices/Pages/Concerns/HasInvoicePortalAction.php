<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrm\Models\Invoice;

trait HasInvoicePortalAction
{
    protected function invoicePortalAction(): Action
    {
        return Action::make('portal')
            ->label('Open portal')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn (Invoice $record): string => url('p/invoices/'.$record->external_id))
            ->openUrlInNewTab();
    }
}
