<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\Feature;

class FeatureActivityStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?Feature $record = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $feature = $this->record;

        $votes = $feature?->votes_count !== null ? (string) $feature->votes_count : '—';
        $comments = $feature?->comments_count !== null ? (string) $feature->comments_count : '—';
        $views = $feature?->views_count !== null ? (string) $feature->views_count : '—';

        return [
            Stat::make(__('laravel-crm-filament::labels.fields.votes'), $votes),

            Stat::make(__('laravel-crm-filament::labels.fields.comments'), $comments),

            Stat::make(__('laravel-crm-filament::labels.fields.views'), $views),
        ];
    }
}
