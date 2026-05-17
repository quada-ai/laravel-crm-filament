<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Task;

class CrmStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Pipeline overview';

    protected function getStats(): array
    {
        return [
            Stat::make('Open leads', (string) Lead::query()->whereNull('converted_at')->count())
                ->description('Leads not yet converted')
                ->color('primary'),

            Stat::make('Open deals', (string) Deal::query()->whereNull('closed_at')->count())
                ->description('Deals in progress')
                ->color('success'),

            Stat::make('Tasks due today', (string) Task::query()
                ->whereNull('completed_at')
                ->whereDate('due_at', today())
                ->count())
                ->description('Activity for today')
                ->color('warning'),
        ];
    }
}


