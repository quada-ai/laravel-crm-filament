<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Industries\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Industries\IndustryResource;

class CreateIndustry extends CreateRecord
{
    protected static string $resource = IndustryResource::class;
}
