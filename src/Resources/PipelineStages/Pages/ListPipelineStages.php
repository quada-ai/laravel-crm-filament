<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

class ListPipelineStages extends ListRecords
{
    protected static string $resource = PipelineStageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
