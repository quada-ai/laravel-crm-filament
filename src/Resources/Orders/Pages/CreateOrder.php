<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Orders\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\OrderAddressTabs;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (empty($data['currency'])) {
            $data['currency'] = app('laravel-crm.settings')->get('currency')
                ?: config('laravel-crm.default_currency', 'USD');
        }

        $person = isset($data['person_id']) ? Person::find($data['person_id']) : null;
        $organization = isset($data['organization_id']) ? Organization::find($data['organization_id']) : null;

        $data['addresses'] = OrderAddressTabs::fromFormData($data);

        $record = app(OrderService::class)->create(FormPayload::wrap($data), $person, $organization);
        OrderResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
