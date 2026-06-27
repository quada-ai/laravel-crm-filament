<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\ProductAttributeResource;

class CreateProductAttribute extends CreateRecord
{
    protected static string $resource = ProductAttributeResource::class;
}
