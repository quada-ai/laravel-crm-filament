<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductCategories\ProductCategoryResource;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
