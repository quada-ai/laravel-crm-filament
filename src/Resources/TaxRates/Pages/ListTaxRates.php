<?php

namespace VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;

class ListTaxRates extends ListRecords
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
