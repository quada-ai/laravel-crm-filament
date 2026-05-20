<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Calls\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Calls\CallResource;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
