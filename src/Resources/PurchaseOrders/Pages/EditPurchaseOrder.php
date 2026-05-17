<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditPurchaseOrder extends EditRecord
{
    use Concerns\HasPurchaseOrderSendAction;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            $this->purchaseOrderSendAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PurchaseOrder $po */
        $po = $this->record;

        foreach (['sub_total', 'tax', 'total'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null) {
                $data[$field] = $value / 100;
            }
        }

        $data['products'] = $po->purchaseOrderLines
            ->map(fn ($line) => [
                'purchase_order_line_id' => $line->id,
                'order_product_id' => $line->order_product_id,
                'id' => $line->product_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->price !== null ? $line->price / 100 : 0,
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        return PurchaseOrderResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // PurchaseOrderService::update reads $request->purchaseOrderLines for round-trip,
        // while create reads $request->products. Provide both so the same form key works.
        $data['purchaseOrderLines'] = $data['products'] ?? [];

        /** @var PurchaseOrder $record */
        app(PurchaseOrderService::class)->update(
            FormPayload::wrap($data),
            $record,
            $record->person,
            $record->organization,
        );
        PurchaseOrderResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }
}
