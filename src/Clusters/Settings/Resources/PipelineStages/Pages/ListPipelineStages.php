<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;

class ListPipelineStages extends ListRecords
{
    protected static string $resource = PipelineStageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
