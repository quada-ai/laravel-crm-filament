<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Services\DeliveryService;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Delivery $delivery */
        $delivery = $this->record;

        $data['products'] = $delivery->deliveryProducts
            ->map(fn ($line) => [
                'order_product_id' => $line->order_product_id,
                'quantity' => $line->quantity,
            ])
            ->all();

        $data['addresses'] = $delivery->addresses
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
        /** @var Delivery $record */
        app(DeliveryService::class)->update(
            FormPayload::wrap($data),
            $record,
            $record->order?->person,
            $record->order?->organization,
        );

        return $record->refresh();
    }
}
