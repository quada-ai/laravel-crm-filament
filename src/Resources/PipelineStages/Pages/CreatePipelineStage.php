<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

class CreatePipelineStage extends CreateRecord
{
    protected static string $resource = PipelineStageResource::class;
}
