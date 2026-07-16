<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\SmsCampaign;

class SmsCampaignStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?SmsCampaign $record = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $campaign = $this->record;

        $recipients = $campaign?->total_recipients !== null
            ? (string) $campaign->total_recipients
            : '—';

        $delivered = $campaign?->delivered_count !== null
            ? (string) $campaign->delivered_count
            : '—';

        $clicks = $campaign?->unique_clicks_count !== null
            ? (string) $campaign->unique_clicks_count
            : '—';

        $unsubscribes = $campaign?->unsubscribes_count !== null
            ? (string) $campaign->unsubscribes_count
            : '—';

        return [
            Stat::make(__('laravel-crm-filament::labels.campaign.recipients'), $recipients),

            Stat::make(__('laravel-crm-filament::labels.campaign.delivered'), $delivered)
                ->description($campaign ? $campaign->deliveryRate() . '% ' . __('laravel-crm-filament::labels.campaign.delivery_rate') : '—'),

            Stat::make(__('laravel-crm-filament::labels.campaign.clicks'), $clicks)
                ->description($campaign ? $campaign->clickRate() . '% ' . __('laravel-crm-filament::labels.campaign.click_rate') : '—'),

            Stat::make(__('laravel-crm-filament::labels.campaign.unsubscribed'), $unsubscribes)
                ->description($campaign ? $campaign->unsubscribeRate() . '% ' . __('laravel-crm-filament::labels.campaign.unsubscribe_rate') : '—'),
        ];
    }
}
