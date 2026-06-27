<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;

class EditLabel extends EditRecord
{
    protected static string $resource = LabelResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
