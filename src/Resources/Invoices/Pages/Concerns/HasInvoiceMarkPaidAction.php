<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\Concerns;

use Filament\Actions\Action;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;

trait HasInvoiceMarkPaidAction
{
    protected function invoiceMarkPaidAction(): Action
    {
        return InvoiceResource::markPaidAction();
    }
}
