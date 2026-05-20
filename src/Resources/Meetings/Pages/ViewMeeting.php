<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Meetings\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\MeetingResource;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
