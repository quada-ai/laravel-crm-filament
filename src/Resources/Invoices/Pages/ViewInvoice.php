<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;

class ViewInvoice extends ViewRecord
{
    use Concerns\HasInvoicePortalAction;
    use Concerns\HasInvoiceSendAction;
    use HasXeroSyncStateInfolist;

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

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            static::xeroSyncStateSection(fn (Invoice $invoice) => $invoice->xeroInvoice),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
            $this->getInfolistContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
