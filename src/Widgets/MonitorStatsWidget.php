<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\Monitor;

class MonitorStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?Monitor $record = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $monitor = $this->record;

        $statusLabel = $monitor?->last_status ? ucfirst($monitor->last_status) : '—';
        $statusColor = match ($monitor?->last_status) {
            'up' => 'success',
            'down' => 'danger',
            'slow' => 'warning',
            default => 'gray',
        };

        $responseTime = $monitor?->last_response_time !== null
            ? $monitor->last_response_time . ' ms'
            : '—';

        $statusCode = $monitor?->last_status_code !== null
            ? (string) $monitor->last_status_code
            : '—';

        $lastChecked = $monitor?->last_checked_at?->format('Y-m-d H:i') ?? '—';

        return [
            Stat::make(__('laravel-crm-filament::labels.fields.status'), $statusLabel)
                ->color($statusColor),

            Stat::make(__('laravel-crm-filament::labels.fields.response_time'), $responseTime),

            Stat::make(__('laravel-crm-filament::labels.fields.status_code'), $statusCode),

            Stat::make(__('laravel-crm-filament::labels.fields.last_checked'), $lastChecked),
        ];
    }
}
