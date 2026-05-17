<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\FieldGroupResource;

class EditFieldGroup extends EditRecord
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
