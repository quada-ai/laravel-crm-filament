<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Fields\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;

class ListFields extends ListRecords
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
