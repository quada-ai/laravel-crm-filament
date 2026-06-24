<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Customers\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()->color('gray')];
    }
}
