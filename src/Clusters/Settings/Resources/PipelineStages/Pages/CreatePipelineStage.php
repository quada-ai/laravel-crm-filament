<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;

class CreatePipelineStage extends CreateRecord
{
    protected static string $resource = PipelineStageResource::class;
}
