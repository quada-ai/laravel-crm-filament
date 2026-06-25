<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureView;

class FeatureViewsChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?Feature $record = null;

    public ?string $filter = 'last_30_days';

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.sales.views_over_time');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => __('laravel-crm-filament::labels.sales.today'),
            'yesterday' => __('laravel-crm-filament::labels.sales.yesterday'),
            'last_7_days' => __('laravel-crm-filament::labels.sales.last_7_days'),
            'last_30_days' => __('laravel-crm-filament::labels.sales.last_30_days'),
            'last_90_days' => __('laravel-crm-filament::labels.sales.last_90_days'),
            'last_365_days' => __('laravel-crm-filament::labels.sales.last_365_days'),
            'this_month' => __('laravel-crm-filament::labels.sales.this_month'),
            'last_month' => __('laravel-crm-filament::labels.sales.last_month'),
            'this_quarter' => __('laravel-crm-filament::labels.sales.this_quarter'),
            'last_quarter' => __('laravel-crm-filament::labels.sales.last_quarter'),
            'this_year' => __('laravel-crm-filament::labels.sales.this_year'),
            'last_year' => __('laravel-crm-filament::labels.sales.last_year'),
            'all_time' => __('laravel-crm-filament::labels.sales.all_time'),
        ];
    }

    protected function getData(): array
    {
        $feature = $this->record;

        if (! $feature) {
            return ['datasets' => [], 'labels' => []];
        }

        [$start, $end, $bucket, $format] = $this->chartRange($feature);

        $views = FeatureView::query()
            ->where('feature_id', $feature->id)
            ->whereBetween('viewed_at', [$start, $end])
            ->orderBy('viewed_at')
            ->get(['viewed_at']);

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $buckets[$cursor->copy()->getTimestamp()] = ['label' => $cursor->format($format), 'count' => 0];
            $cursor = $this->advanceCursor($cursor, $bucket);
        }

        foreach ($views as $view) {
            $key = $this->bucketKey(Carbon::parse($view->viewed_at), $start, $bucket);

            if ($key !== null && isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }

        $labels = [];
        $data = [];

        foreach ($buckets as $b) {
            $labels[] = $b['label'];
            $data[] = $b['count'];
        }

        return [
            'datasets' => [
                [
                    'label' => __('laravel-crm-filament::labels.sales.views_over_time'),
                    'data' => $data,
                    'backgroundColor' => '#05b3a9',
                    'borderColor' => '#05b3a9',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function chartRange(Feature $feature): array
    {
        $now = Carbon::now();

        return match ($this->filter) {
            'today' => [today()->startOfDay(), $now, 'hour', 'H:i'],
            'yesterday' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay(), 'hour', 'H:i'],
            'last_7_days' => [today()->subDays(6)->startOfDay(), $now, 'day', 'M j'],
            'last_30_days' => [today()->subDays(29)->startOfDay(), $now, 'day', 'M j'],
            'last_90_days' => [today()->subDays(89)->startOfDay(), $now, 'day', 'M j'],
            'last_365_days' => [today()->subDays(364)->startOfDay(), $now, 'week', 'M j'],
            'last_month' => [today()->subMonth()->startOfMonth()->startOfDay(), today()->subMonth()->endOfMonth()->endOfDay(), 'day', 'M j'],
            'this_quarter' => [today()->startOfQuarter()->startOfDay(), $now, 'week', 'M j'],
            'last_quarter' => [today()->subQuarter()->startOfQuarter()->startOfDay(), today()->subQuarter()->endOfQuarter()->endOfDay(), 'week', 'M j'],
            'this_year' => [today()->startOfYear()->startOfDay(), $now, 'month', 'M Y'],
            'last_year' => [today()->subYear()->startOfYear()->startOfDay(), today()->subYear()->endOfYear()->endOfDay(), 'month', 'M Y'],
            'all_time' => [$this->allTimeStart($feature, $now), $now, 'month', 'M Y'],
            default => [today()->startOfMonth()->startOfDay(), $now, 'day', 'M j'],
        };
    }

    protected function allTimeStart(Feature $feature, CarbonInterface $now): CarbonInterface
    {
        $earliest = FeatureView::query()
            ->where('feature_id', $feature->id)
            ->min('viewed_at');

        return $earliest ? Carbon::parse($earliest) : $now->copy()->subMonth();
    }

    protected function advanceCursor(CarbonInterface $cursor, string $bucket): CarbonInterface
    {
        return match ($bucket) {
            'hour' => $cursor->copy()->addHour(),
            'day' => $cursor->copy()->addDay(),
            'week' => $cursor->copy()->addWeek(),
            'month' => $cursor->copy()->addMonth(),
            default => $cursor->copy()->addDay(),
        };
    }

    protected function bucketKey(CarbonInterface $when, CarbonInterface $start, string $bucket): ?int
    {
        if ($when->lt($start)) {
            return null;
        }

        if ($bucket === 'month') {
            $monthsDiff = ($when->year - $start->year) * 12 + ($when->month - $start->month);

            return $start->copy()->addMonths($monthsDiff)->getTimestamp();
        }

        $sizeSeconds = match ($bucket) {
            'hour' => 3600,
            'day' => 86400,
            'week' => 7 * 86400,
            default => 86400,
        };

        $delta = $when->getTimestamp() - $start->getTimestamp();
        $index = (int) floor($delta / $sizeSeconds);

        return $start->copy()->addSeconds($index * $sizeSeconds)->getTimestamp();
    }
}
