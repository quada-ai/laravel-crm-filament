<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\ProductAttributeResource;

class EditProductAttribute extends EditRecord
{
    protected static string $resource = ProductAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
