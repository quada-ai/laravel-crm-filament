<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Notes\Pages;

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Notes\NoteResource;

class ViewNote extends ViewRecord
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
