<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadSources\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadSources\LeadSourceResource;

class CreateLeadSource extends CreateRecord
{
    protected static string $resource = LeadSourceResource::class;
}
