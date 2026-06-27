<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
