<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Users\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Users\UserResource;
use VentureDrake\LaravelCrmFilament\Concerns\Imports\UserImporter;
use VentureDrake\LaravelCrmFilament\Concerns\ImportsCsv;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportsCsv::action(UserImporter::class),
            Actions\CreateAction::make(),
        ];
    }
}
