<?php

namespace VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;

class EditLeadSource extends EditRecord
{
    protected static string $resource = LeadSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
