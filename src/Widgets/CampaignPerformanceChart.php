<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\EmailCampaign;

class CampaignPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Recent campaign performance';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $campaigns = EmailCampaign::query()
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get();

        $campaigns = $campaigns->reverse()->values();

        return [
            'datasets' => [
                [
                    'label' => 'Open rate (%)',
                    'data' => $campaigns->map(fn (EmailCampaign $c) => $c->openRate())->all(),
                    'backgroundColor' => '#05b3a9',
                ],
                [
                    'label' => 'Click rate (%)',
                    'data' => $campaigns->map(fn (EmailCampaign $c) => $c->clickRate())->all(),
                    'backgroundColor' => '#6505B3',
                ],
            ],
            'labels' => $campaigns->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
