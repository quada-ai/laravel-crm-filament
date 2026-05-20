<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Customers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Services\CustomerService;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CustomerService::class)->create(FormPayload::wrap($data));
    }
}
