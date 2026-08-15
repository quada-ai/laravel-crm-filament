<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Models\MonitorCheck;
use VentureDrake\LaravelCrmFilament\Concerns\HasChartRangeFilter;

class MonitorResponseTimeChart extends ChartWidget
{
    use HasChartRangeFilter;

    protected int | string | array $columnSpan = 'full';

    public ?Monitor $record = null;

    public ?string $filter = 'last_7_days';

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.sales.average_response_time');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function defaultFilter(): string
    {
        return 'last_7_days';
    }

    protected function getData(): array
    {
        $monitor = $this->record;

        if (! $monitor) {
            return ['datasets' => [], 'labels' => []];
        }

        [$start, $end, $bucket, $format] = $this->chartRange();

        $checks = MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('type', 'uptime')
            ->whereBetween('checked_at', [$start, $end])
            ->whereNotNull('response_time')
            ->orderBy('checked_at')
            ->get(['response_time', 'checked_at']);

        $buckets = $this->buildBuckets($start, $end, $bucket, $format, ['values' => []]);

        foreach ($checks as $check) {
            $key = $this->bucketKey($check->checked_at, $start, $bucket);

            if ($key !== null && isset($buckets[$key])) {
                $buckets[$key]['values'][] = (int) $check->response_time;
            }
        }

        $labels = [];
        $data = [];

        foreach ($buckets as $b) {
            $labels[] = $b['label'];
            $data[] = $b['values'] === [] ? 0 : (int) round(array_sum($b['values']) / count($b['values']));
        }

        $datasets = [
            [
                'label' => __('laravel-crm-filament::labels.sales.average_response_time') . ' (ms)',
                'data' => $data,
                'backgroundColor' => '#05b3a9',
                'borderColor' => '#05b3a9',
            ],
        ];

        $threshold = (int) ($monitor->perf_threshold_ms ?? 0);

        if ($threshold > 0 && $labels !== []) {
            $datasets[] = [
                'type' => 'line',
                'label' => __('laravel-crm-filament::labels.sales.performance_threshold') . ' (ms)',
                'data' => array_fill(0, count($labels), $threshold),
                'borderColor' => '#B34105',
                'backgroundColor' => 'transparent',
                'borderDash' => [6, 4],
                'borderWidth' => 2,
                'pointRadius' => 0,
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function allTimeStart(CarbonInterface $now): CarbonInterface
    {
        $monitor = $this->record;

        $earliest = $monitor ? MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('type', 'uptime')
            ->min('checked_at') : null;

        if (! $earliest) {
            return $now->copy()->subMonths(12);
        }

        $parsed = Carbon::parse($earliest);
        if ($parsed->year < 2000) {
            return $now->copy()->subMonths(12);
        }

        return $this->clampStartDate($parsed, $now);
    }
}
