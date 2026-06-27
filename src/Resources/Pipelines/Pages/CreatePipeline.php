<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

class CreatePipeline extends CreateRecord
{
    protected static string $resource = PipelineResource::class;
}
