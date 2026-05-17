<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;

class ViewInvoice extends ViewRecord
{
    use Concerns\HasInvoiceSendAction;
    use Concerns\HasInvoicePortalAction;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            $this->invoiceSendAction(),
            $this->invoicePortalAction(),
        ];
    }
}
