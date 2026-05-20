<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;

class ViewInvoice extends ViewRecord
{
    use Concerns\HasInvoicePortalAction;
    use Concerns\HasInvoiceSendAction;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->invoicePortalAction(),
            Actions\EditAction::make(),
            $this->invoiceSendAction(),
            $this->invoiceDownloadPdfAction(),
        ];
    }
}
