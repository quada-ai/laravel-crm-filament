<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\FeatureStatusResource;

class EditFeatureStatus extends EditRecord
{
    protected static string $resource = FeatureStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
