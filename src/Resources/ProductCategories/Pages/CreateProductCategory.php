<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
