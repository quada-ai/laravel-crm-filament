<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\TaxRateResource;

class CreateTaxRate extends CreateRecord
{
    protected static string $resource = TaxRateResource::class;
}
