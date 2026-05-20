<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Notes\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Notes\NoteResource;

class ListNotes extends ListRecords
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
