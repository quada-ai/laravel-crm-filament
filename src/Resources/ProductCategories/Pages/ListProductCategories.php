<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
