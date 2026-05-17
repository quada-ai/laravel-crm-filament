<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\PipelineResource;

class CreatePipeline extends CreateRecord
{
    protected static string $resource = PipelineResource::class;
}
