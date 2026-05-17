<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;

class EditPipelineStage extends EditRecord
{
    protected static string $resource = PipelineStageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
