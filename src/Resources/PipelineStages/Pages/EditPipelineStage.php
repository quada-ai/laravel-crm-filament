<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\PipelineStageProbability;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

class EditPipelineStage extends EditRecord
{
    protected static string $resource = PipelineStageResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\ColorPicker::make('color'),
            ]),
            Forms\Components\Select::make('pipeline_stage_probability_id')
                ->label(__('laravel-crm-filament::labels.sales.probability'))
                ->options(fn () => PipelineStageProbability::query()->orderBy('percent')->get()->mapWithKeys(fn ($p) => [$p->id => $p->name . ' (' . $p->percent . '%)']))
                ->searchable()
                ->preload(),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
