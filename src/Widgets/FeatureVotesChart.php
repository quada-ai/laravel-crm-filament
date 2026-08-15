<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureVote;
use VentureDrake\LaravelCrmFilament\Concerns\HasChartRangeFilter;

class FeatureVotesChart extends ChartWidget
{
    use HasChartRangeFilter;

    protected int | string | array $columnSpan = 'full';

    public ?Feature $record = null;

    public ?string $filter = 'last_30_days';

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.sales.votes_over_time');
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

        $votes = FeatureVote::query()
            ->where('feature_id', $feature->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['created_at']);

        $buckets = $this->buildBuckets($start, $end, $bucket, $format, ['count' => 0]);

        foreach ($votes as $vote) {
            $key = $this->bucketKey(Carbon::parse($vote->created_at), $start, $bucket);

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
                    'label' => __('laravel-crm-filament::labels.sales.votes_over_time'),
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

        $earliest = $feature ? FeatureVote::query()
            ->where('feature_id', $feature->id)
            ->min('created_at') : null;

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
