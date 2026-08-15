<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;

trait HasChartRangeFilter
{
    protected function defaultFilter(): string
    {
        return 'last_30_days';
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

    protected function chartRange(): array
    {
        $now = Carbon::now();

        return match ($this->filter ?? $this->defaultFilter()) {
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
            'all_time' => [$this->allTimeStart($now), $now, 'month', 'M Y'],
            default => [today()->startOfMonth()->startOfDay(), $now, 'day', 'M j'],
        };
    }

    protected function allTimeStart(CarbonInterface $now): CarbonInterface
    {
        return $now->copy()->subMonths(12);
    }

    protected function clampStartDate(CarbonInterface $date, CarbonInterface $now, int $maxYearsBack = 5): CarbonInterface
    {
        $floor = $now->copy()->subYears($maxYearsBack)->startOfYear();

        if ($date->lt($floor) || $date->year < 2000) {
            return $floor;
        }

        return $date;
    }

    /**
     * Build time buckets safely with an iteration safeguard to prevent runaway loops.
     *
     * @param array<string, mixed> $template
     * @return array<int, array<string, mixed>>
     */
    protected function buildBuckets(
        CarbonInterface $start,
        CarbonInterface $end,
        string $bucket,
        string $format,
        array $template = [],
        int $maxIterations = 500
    ): array {
        $buckets = [];
        $cursor = $start->copy();
        $iterations = 0;

        while ($cursor->lte($end) && $iterations++ < $maxIterations) {
            $buckets[$cursor->copy()->getTimestamp()] = array_merge(
                ['label' => $cursor->format($format)],
                $template
            );
            $cursor = $this->advanceCursor($cursor, $bucket);
        }

        return $buckets;
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
