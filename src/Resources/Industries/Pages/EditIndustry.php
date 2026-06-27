<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Industries\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\Industries\IndustryResource;

class EditIndustry extends EditRecord
{
    protected static string $resource = IndustryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
