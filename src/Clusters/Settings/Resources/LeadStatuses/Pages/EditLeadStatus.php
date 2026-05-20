<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\LeadStatusResource;

class EditLeadStatus extends EditRecord
{
    protected static string $resource = LeadStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
