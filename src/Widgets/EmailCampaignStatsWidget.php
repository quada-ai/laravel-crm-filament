<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrmFilament\Pages\Dashboard;

class EmailCampaignStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?EmailCampaign $record = null;

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

        $opens = $campaign?->unique_opens_count !== null
            ? (string) $campaign->unique_opens_count
            : '—';

        $clicks = $campaign?->unique_clicks_count !== null
            ? (string) $campaign->unique_clicks_count
            : '—';

        $unsubscribes = $campaign?->unsubscribes_count !== null
            ? (string) $campaign->unsubscribes_count
            : '—';

        return [
            Stat::make(__('laravel-crm-filament::labels.campaign.recipients'), $recipients),

            Stat::make(__('laravel-crm-filament::labels.campaign.opens'), $opens)
                ->description($campaign ? $campaign->openRate() . '% ' . __('laravel-crm-filament::labels.campaign.open_rate') : '—'),

            Stat::make(__('laravel-crm-filament::labels.campaign.clicks'), $clicks)
                ->description($campaign ? $campaign->clickRate() . '% ' . __('laravel-crm-filament::labels.campaign.click_rate') : '—'),

            Stat::make(__('laravel-crm-filament::labels.campaign.unsubscribed'), $unsubscribes)
                ->description($campaign ? $campaign->unsubscribeRate() . '% ' . __('laravel-crm-filament::labels.campaign.unsubscribe_rate') : '—'),
        ];
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
