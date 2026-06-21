<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Customers\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Services\CustomerService;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Customer $record */
        app(CustomerService::class)->update($record, FormPayload::wrap($data));

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
