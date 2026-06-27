<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\ProductAttributeResource;

class ListProductAttributes extends ListRecords
{
    protected static string $resource = ProductAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
