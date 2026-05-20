<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStageProbabilities\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStageProbabilities\PipelineStageProbabilityResource;

class ListPipelineStageProbabilities extends ListRecords
{
    protected static string $resource = PipelineStageProbabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
