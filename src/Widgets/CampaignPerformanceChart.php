<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrmFilament\Pages\Dashboard;

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

    public static function canView(): bool
    {
        // Hidden from the plugin Dashboard — matches core /crm/dashboard
        // which doesn't surface campaign performance/stats widgets. Still
        // usable as a getHeaderWidgets() entry on ViewEmailCampaign /
        // ViewSmsCampaign show pages (the check is scoped to Dashboard).
        $component = Livewire::current();
        if ($component instanceof Dashboard) {
            return false;
        }

        return true;
    }
}
