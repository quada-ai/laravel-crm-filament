<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

class ListPipelines extends ListRecords
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
