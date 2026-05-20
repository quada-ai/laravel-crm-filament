<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Lunches\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\LunchResource;

class ListLunches extends ListRecords
{
    protected static string $resource = LunchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
