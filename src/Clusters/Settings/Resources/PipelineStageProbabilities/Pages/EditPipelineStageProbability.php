<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStageProbabilities\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStageProbabilities\PipelineStageProbabilityResource;

class EditPipelineStageProbability extends EditRecord
{
    protected static string $resource = PipelineStageProbabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
