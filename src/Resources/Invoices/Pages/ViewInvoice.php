<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;

class ViewInvoice extends ViewRecord
{
    use Concerns\HasInvoiceMarkPaidAction;
    use Concerns\HasInvoicePortalAction;
    use Concerns\HasInvoiceSendAction;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->invoicePortalAction()
                ->button()
                ->hiddenLabel(),
            $this->invoiceMarkPaidAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            $this->invoiceSendAction(),
            $this->invoiceDownloadPdfAction(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
