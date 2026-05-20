<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Widgets\CampaignPerformanceChart;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;

class Dashboard extends BaseDashboard
{
    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $widgets = [
            CrmStatsOverview::class,
            DealsValueStat::class,
            LeadsByStageChart::class,
            MonthlyRevenueChart::class,
            TasksDueTodayList::class,
            RecentActivityList::class,
        ];

        if (static::campaignsModuleEnabled()) {
            $widgets[] = CampaignPerformanceChart::class;
        }

        return $widgets;
    }

    /**
     * Mirror the same gating the plugin applies when deciding to register
     * CampaignPerformanceChart on the panel: prefer the plugin instance bound
     * to the current panel, fall back to the core CRM modules config.
     */
    protected static function campaignsModuleEnabled(): bool
    {
        $panel = Filament::getCurrentPanel();
        if ($panel) {
            try {
                $plugin = $panel->getPlugin('laravel-crm');
                if ($plugin instanceof LaravelCrmPlugin) {
                    return $plugin->isModuleEnabled('email-marketing');
                }
            } catch (\Throwable) {
                // Panel has no plugin under this id; the panel's widget list is
                // still the authoritative source — register() decided what to
                // include based on the same gate.
            }

            return in_array(CampaignPerformanceChart::class, $panel->getWidgets(), true);
        }

        return in_array('email-marketing', (array) config('laravel-crm.modules', []), true);
    }
}
