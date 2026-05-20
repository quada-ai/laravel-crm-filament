<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroItemResource;

class ViewXeroItem extends ViewRecord
{
    protected static string $resource = XeroItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
