<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\ContactTypeResource;

class EditContactType extends EditRecord
{
    protected static string $resource = ContactTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
