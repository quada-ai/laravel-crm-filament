<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroContactResource;

class ViewXeroContact extends ViewRecord
{
    protected static string $resource = XeroContactResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
