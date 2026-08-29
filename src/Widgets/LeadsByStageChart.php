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
        $pipeline = DefaultPipeline::ensureFor(Lead::class);

        $stages = PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('order')
            ->get();

        $stageIds = $stages->pluck('id')->all();
        $defaultStageId = $stages->first()?->id;

        $leads = Lead::query()
            ->whereNull('converted_at')
            ->get(['id', 'pipeline_stage_id']);

        $counts = [];
        foreach ($leads as $lead) {
            $stageId = $lead->pipeline_stage_id;
            if (! $stageId || ! in_array($stageId, $stageIds)) {
                $stageId = $defaultStageId;
            }

            if ($stageId) {
                $counts[$stageId] = ($counts[$stageId] ?? 0) + 1;
            }
        }

        $labels = $stages->map(function ($stage) {
            $key = 'laravel-crm-filament::labels.stages.' . Str::snake(str_replace('-', ' ', $stage->name));
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

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
