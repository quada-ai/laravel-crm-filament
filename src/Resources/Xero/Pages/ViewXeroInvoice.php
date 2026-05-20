<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroInvoiceResource;

class ViewXeroInvoice extends ViewRecord
{
    protected static string $resource = XeroInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
