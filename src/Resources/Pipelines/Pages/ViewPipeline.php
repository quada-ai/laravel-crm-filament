<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages;

use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

class ViewPipeline extends ViewRecord
{
    protected static string $resource = PipelineResource::class;

    public function getTitle(): string
    {
        return __('laravel-crm-filament::labels.sales.pipeline') . ': ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            PipelineResource::backToIndexAction(),
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

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make(__('laravel-crm-filament::labels.sales.stages'))
                    ->schema([
                        TextEntry::make('stages_list')
                            ->hiddenLabel()
                            ->listWithLineBreaks()
                            ->state($record ? $record->pipelineStages()->orderBy('order')->pluck('name')->all() : [])
                            ->placeholder(__('laravel-crm-filament::labels.misc.none')),
                    ])
                    ->columnSpan(['lg' => 1]),
                Section::make(__('laravel-crm-filament::labels.sales.attached_to'))
                    ->schema([
                        TextEntry::make('attached_to')
                            ->hiddenLabel()
                            ->state($record ? class_basename($record->model) : ''),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]),
        ]);
    }
}
