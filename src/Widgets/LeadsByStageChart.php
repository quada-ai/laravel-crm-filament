<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Support\DefaultPipeline;

class LeadsByStageChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.dashboard.leads_by_pipeline_stage');
    }

    protected function getData(): array
    {
        $pipelineIds = Pipeline::query()
            ->whereIn('model', [Lead::class, 'Lead', 'lead'])
            ->pluck('id');

        if ($pipelineIds->isEmpty()) {
            $defaultPipeline = DefaultPipeline::ensureFor(Lead::class);
            $pipelineIds = collect([$defaultPipeline->id]);
        }

        $stages = PipelineStage::query()
            ->whereIn('pipeline_id', $pipelineIds)
            ->orderBy('order')
            ->get();

        if ($stages->isEmpty()) {
            $defaultPipeline = DefaultPipeline::ensureFor(Lead::class);
            $stages = PipelineStage::query()
                ->where('pipeline_id', $defaultPipeline->id)
                ->orderBy('order')
                ->get();
        }

        $counts = Lead::query()
            ->whereNull('converted_at')
            ->whereIn('pipeline_stage_id', $stages->pluck('id'))
            ->selectRaw('pipeline_stage_id, COUNT(*) as total')
            ->groupBy('pipeline_stage_id')
            ->pluck('total', 'pipeline_stage_id');

        $labels = $stages->map(function ($stage) {
            $key = 'laravel-crm-filament::labels.stages.' . Str::snake($stage->name);
            $trans = __($key);

            return ($trans !== $key) ? $trans : __($stage->name);
        })->all();

        return [
            'datasets' => [
                [
                    'label' => __('laravel-crm-filament::labels.dashboard.open_leads'),
                    'data' => $stages->map(fn ($s) => (int) ($counts[$s->id] ?? 0))->all(),
                    'backgroundColor' => '#05b3a9',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
