<?php

namespace VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;

class CreateTaxRate extends CreateRecord
{
    protected static string $resource = TaxRateResource::class;
}
