<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditInvoice extends EditRecord
{
    use Concerns\HasInvoiceSendAction;
    use Concerns\HasInvoicePortalAction;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            $this->invoiceSendAction(),
            $this->invoicePortalAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->record;

        foreach (['sub_total', 'tax', 'total'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null) {
                $data[$field] = $value / 100;
            }
        }

        $data['products'] = $invoice->invoiceLines
            ->map(fn ($line) => [
                'invoice_line_id' => $line->id,
                'order_product_id' => $line->order_product_id,
                'id' => $line->product_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->price !== null ? $line->price / 100 : 0,
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        return InvoiceResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Invoice $record */
        app(InvoiceService::class)->update(
            FormPayload::wrap($data),
            $record,
            $record->person,
            $record->organization,
        );
        InvoiceResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }
}
