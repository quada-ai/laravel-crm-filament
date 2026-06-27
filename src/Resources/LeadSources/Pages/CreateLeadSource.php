<?php

namespace VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;

class CreateLeadSource extends CreateRecord
{
    protected static string $resource = LeadSourceResource::class;
}
