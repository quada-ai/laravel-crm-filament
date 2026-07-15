<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureView;
use VentureDrake\LaravelCrmFilament\Concerns\HasChartRangeFilter;

class FeatureViewsChart extends ChartWidget
{
    use HasChartRangeFilter;

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

    protected function getData(): array
    {
        $feature = $this->record;

        if (! $feature) {
            return ['datasets' => [], 'labels' => []];
        }

        [$start, $end, $bucket, $format] = $this->chartRange();

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

    protected function allTimeStart(CarbonInterface $now): CarbonInterface
    {
        $feature = $this->record;

        $earliest = $feature ? FeatureView::query()
            ->where('feature_id', $feature->id)
            ->min('viewed_at') : null;

        return $earliest ? Carbon::parse($earliest) : $now->copy()->subMonth();
    }
}
