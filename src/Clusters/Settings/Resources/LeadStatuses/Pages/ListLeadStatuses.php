<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\LeadStatusResource;

class ListLeadStatuses extends ListRecords
{
    protected static string $resource = LeadStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
