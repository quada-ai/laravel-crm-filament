<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

class EditPipeline extends EditRecord
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
