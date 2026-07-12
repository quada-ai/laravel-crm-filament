<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Core ProductCategory has no observer to stamp external_id — set it ourselves.
        $data['external_id'] ??= (string) Uuid::uuid4();

        return $data;
    }
}
