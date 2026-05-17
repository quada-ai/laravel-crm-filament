<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\DeliveryService;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(DeliveryService::class)->create(FormPayload::wrap($data));
    }
}
