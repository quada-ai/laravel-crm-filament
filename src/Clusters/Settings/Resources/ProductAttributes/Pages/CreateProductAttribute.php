<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductAttributes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductAttributes\ProductAttributeResource;

class CreateProductAttribute extends CreateRecord
{
    protected static string $resource = ProductAttributeResource::class;
}
