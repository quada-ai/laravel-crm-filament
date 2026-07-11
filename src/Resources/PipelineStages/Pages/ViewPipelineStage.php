<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

class ViewPipelineStage extends ViewRecord
{
    protected static string $resource = PipelineStageResource::class;

    public function getTitle(): string
    {
        return __('laravel-crm-filament::labels.sales.pipeline_stage') . ': ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            PipelineStageResource::backToIndexAction(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        // Capture the record now (page has it bound by the time content() runs)
        // so the closures below can read it directly instead of relying on
        // Filament's parameter injection, which was firing with $record=null
        // when nested Section schemas were evaluated in some render phases.
        // See commit 54b6a24 for the recurring bug this pattern avoids.
        $record = $this->record;

        $colorHex = $record?->color ? '#' . ltrim($record->color, '#') : '#6b7280';

        $probabilityState = $record?->pipelineStageProbability
            ? $record->pipelineStageProbability->name . ' (' . $record->pipelineStageProbability->percent . '%)'
            : null;

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make(__('laravel-crm-filament::labels.sales.pipeline_stage'))
                    ->heading(__('laravel-crm-filament::labels.sections.details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('laravel-crm-filament::labels.fields.name'))
                            ->state($record?->name),
                        TextEntry::make('order')
                            ->label(__('laravel-crm-filament::labels.fields.order'))
                            ->state($record?->order)
                            ->hidden(),
                        TextEntry::make('color')
                            ->label(__('laravel-crm-filament::labels.fields.color'))
                            ->badge()
                            ->state($colorHex)
                            ->color($colorHex)
                            ->hidden(),
                        TextEntry::make('description')
                            ->label(__('laravel-crm-filament::labels.fields.description'))
                            ->state($record?->description)
                            ->columnSpanFull(),
                        TextEntry::make('pipeline_stage_probability_id')
                            ->label(__('laravel-crm-filament::labels.sales.probability'))
                            ->state($probabilityState)
                            ->placeholder(__('laravel-crm-filament::labels.misc.none'))
                            ->hidden(),
                    ])
                    ->columnSpan(['lg' => 1]),
                Section::make(__('laravel-crm-filament::labels.sales.attached_to'))
                    ->schema([
                        TextEntry::make('attached_to')
                            ->hiddenLabel()
                            ->state($record?->pipeline?->name ?? ''),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
