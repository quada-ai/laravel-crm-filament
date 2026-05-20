<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroInvoiceResource;

class ListXeroInvoices extends ListRecords
{
    protected static string $resource = XeroInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
