<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditInvoice extends EditRecord
{
    use Concerns\HasInvoiceMarkPaidAction;
    use Concerns\HasInvoicePortalAction;
    use Concerns\HasInvoiceSendAction;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->invoiceMarkPaidAction(),
            Actions\ViewAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-eye'),
            $this->invoiceSendAction(),
            $this->invoiceDownloadPdfAction(),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->record;

        if (isset($data['subtotal']) && ! isset($data['sub_total'])) {
            $data['sub_total'] = $data['subtotal'];
        }

        foreach (['sub_total', 'discount', 'tax', 'total'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null) {
                $data[$field] = $value / 100;
            }
        }

        $data['adjustment'] = isset($data['adjustments']) && $data['adjustments'] !== null
            ? $data['adjustments'] / 100
            : null;

        $data['products'] = $invoice->invoiceLines
            ->map(fn ($line) => [
                'invoice_line_id' => $line->id,
                'order_product_id' => $line->order_product_id,
                'id' => $line->product_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->price !== null ? $line->price / 100 : 0,
                'tax_amount' => $line->tax_amount !== null ? $line->tax_amount / 100 : 0,
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        if (empty($data['sub_total']) && ! empty($data['products'])) {
            $sub = 0;
            $tax = 0;
            foreach ($data['products'] as $p) {
                $sub += (float) ($p['amount'] ?? 0);
                $tax += (float) ($p['tax_amount'] ?? 0);
            }
            $data['sub_total'] = $sub;
            if (empty($data['tax'])) {
                $data['tax'] = $tax;
            }
            if (empty($data['total'])) {
                $discount = (float) ($data['discount'] ?? 0);
                $adjustment = (float) ($data['adjustment'] ?? 0);
                $data['total'] = max(0, $sub - $discount + $tax + $adjustment);
            }
        }

        return InvoiceResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Invoice $record */
        $person = isset($data['person_id'])
            ? Person::find($data['person_id'])
            : $record->person;
        $organization = isset($data['organization_id'])
            ? Organization::find($data['organization_id'])
            : $record->organization;

        app(InvoiceService::class)->update(
            FormPayload::wrap($data),
            $record,
            $person,
            $organization,
        );

        // InvoiceService doesn't update discount/adjustments — write them
        // directly so the 5-rollup money row reaches the DB.
        $extras = [];
        if (array_key_exists('discount', $data)) {
            $extras['discount'] = $data['discount'] !== null
                ? (int) round(((float) $data['discount']) * 100)
                : null;
        }
        if (array_key_exists('adjustment', $data)) {
            $extras['adjustments'] = $data['adjustment'] !== null
                ? (int) round(((float) $data['adjustment']) * 100)
                : null;
        }
        if ($extras !== []) {
            $record->forceFill($extras)->save();
        }

        InvoiceResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
