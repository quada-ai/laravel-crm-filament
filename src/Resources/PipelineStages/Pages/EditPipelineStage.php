<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

class EditPipelineStage extends EditRecord
{
    protected static string $resource = PipelineStageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
