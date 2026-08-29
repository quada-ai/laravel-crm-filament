<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Support\DefaultPipeline;

class DealsPipelineValueChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.dashboard.pipeline_by_stage_deals');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Timeless chart per AC — no period filter dropdown.
     */
    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getData(): array
    {
        $pipeline = DefaultPipeline::ensureFor(Deal::class);

        $stages = PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('order')
            ->get();

        $values = $stages->map(function ($stage) {
            $sumCents = (int) Deal::query()
                ->where('pipeline_stage_id', $stage->id)
                ->whereNull('closed_status')
                ->sum('amount');

            return $sumCents / 100;
        })->all();

        $labels = $stages->map(function ($stage) {
            $key = 'laravel-crm-filament::labels.stages.' . Str::snake(str_replace('-', ' ', $stage->name));
            $trans = __($key);

            return ($trans !== $key) ? $trans : __($stage->name);
        })->all();

        return [
            'datasets' => [
                [
                    'label' => __('laravel-crm-filament::labels.dashboard.pipeline_value'),
                    'data' => $values,
                    'backgroundColor' => '#05b3a9',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
