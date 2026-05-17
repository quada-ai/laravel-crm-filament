<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Order $order */
        $order = $this->record;

        foreach (['sub_total', 'discount', 'tax', 'total'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null) {
                $data[$field] = $value / 100;
            }
        }

        $data['adjustment'] = isset($data['adjustments']) && $data['adjustments'] !== null
            ? $data['adjustments'] / 100
            : null;

        $data['products'] = $order->orderProducts
            ->map(fn ($line) => [
                'order_product_id' => $line->id,
                'id' => $line->product_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->price !== null ? $line->price / 100 : 0,
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        $data['addresses'] = $order->addresses
            ->map(fn ($address) => [
                'id' => $address->id,
                'address_type_id' => $address->address_type_id,
                'name' => $address->name,
                'line1' => $address->line1,
                'line2' => $address->line2,
                'line3' => $address->line3,
                'city' => $address->city,
                'state' => $address->state,
                'code' => $address->code,
                'country' => $address->country,
                'contact' => $address->contact,
                'phone' => $address->phone,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Order $record */
        app(OrderService::class)->update(
            FormPayload::wrap($data),
            $record,
            $record->person,
            $record->organization,
            $record->client,
        );

        return $record->refresh();
    }
}
