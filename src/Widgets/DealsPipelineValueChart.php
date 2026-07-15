<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class DealsPipelineValueChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

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
        $stages = PipelineStage::query()->orderBy('order')->get();

        $values = $stages->map(function ($stage) {
            $sumCents = (int) Deal::query()
                ->where('pipeline_stage_id', $stage->id)
                ->whereNull('closed_status')
                ->sum('amount');

            return $sumCents / 100;
        })->all();

        return [
            'datasets' => [
                [
                    'label' => __('laravel-crm-filament::labels.dashboard.pipeline_value'),
                    'data' => $values,
                    'backgroundColor' => '#05b3a9',
                ],
            ],
            'labels' => $stages->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
