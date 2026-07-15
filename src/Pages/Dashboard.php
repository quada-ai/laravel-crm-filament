<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Widgets\CampaignPerformanceChart;
use VentureDrake\LaravelCrmFilament\Widgets\ContactsStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsPipelineValueChart;
use VentureDrake\LaravelCrmFilament\Widgets\DealStatusDoughnutChart;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsVsDealsChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;

class Dashboard extends BaseDashboard
{
    /**
     * Layout order per plan:
     *   Sales stats → Finance stats → Contacts stat → Revenue Trend →
     *   Pipeline Value → Leads vs Deals → Deal Status → Leads by Stage →
     *   Upcoming Tasks → Recent Activity → Campaign Performance.
     *
     * ContactsStatsOverview, TasksDueTodayList, RecentActivityList are ungated.
     *
     * Charts land in 2-column rows on the dashboard by overriding columnSpan
     * to 1 at the WidgetConfiguration level; the widget classes themselves
     * keep columnSpan='full' so they still render full-width when reused as
     * footer widgets on other pages (via LaravelCrmPlugin::register()).
     *
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        $widgets = [];

        // Sales stats — CrmStatsOverview's individual stats are gated on
        // leads/deals internally; surface the widget when either module is on.
        if (static::moduleEnabled('leads') || static::moduleEnabled('deals')) {
            $widgets[] = CrmStatsOverview::class;
        }

        // Finance stats — DealsValueStat covers invoices/quotes/orders.
        if (static::moduleEnabled('invoices')
            || static::moduleEnabled('quotes')
            || static::moduleEnabled('orders')) {
            $widgets[] = DealsValueStat::class;
        }

        // Contacts stat — ungated per AC.
        $widgets[] = ContactsStatsOverview::class;

        // Revenue Trend charts paid invoices + orders together.
        if (static::moduleEnabled('invoices') || static::moduleEnabled('orders')) {
            $widgets[] = MonthlyRevenueChart::make(['columnSpan' => 1]);
        }

        // Pipeline Value / Deal Status are deal-only.
        if (static::moduleEnabled('deals')) {
            $widgets[] = DealsPipelineValueChart::make(['columnSpan' => 1]);
        }

        // Leads vs Deals needs at least one of the two modules.
        if (static::moduleEnabled('leads') || static::moduleEnabled('deals')) {
            $widgets[] = LeadsVsDealsChart::make(['columnSpan' => 1]);
        }

        if (static::moduleEnabled('deals')) {
            $widgets[] = DealStatusDoughnutChart::make(['columnSpan' => 1]);
        }

        if (static::moduleEnabled('leads')) {
            $widgets[] = LeadsByStageChart::make(['columnSpan' => 1]);
        }

        // Upcoming Tasks + Recent Activity — ungated per AC.
        $widgets[] = TasksDueTodayList::class;
        $widgets[] = RecentActivityList::class;

        if (static::moduleEnabled('email-marketing')) {
            $widgets[] = CampaignPerformanceChart::make(['columnSpan' => 1]);
        }

        return $widgets;
    }

    /**
     * Resolve whether a given module is enabled for the current panel.
     *
     * Preference order matches the previous `campaignsModuleEnabled()` helper:
     *   1. Ask the plugin instance bound to the current panel.
     *   2. Inspect the panel's registered widgets — register() applied the
     *      same gate when building the widget list.
     *   3. Fall back to the core CRM modules config.
     */
    protected static function moduleEnabled(string $module): bool
    {
        $panel = Filament::getCurrentPanel();
        if ($panel) {
            try {
                $plugin = $panel->getPlugin('laravel-crm');
                if ($plugin instanceof LaravelCrmPlugin) {
                    return $plugin->isModuleEnabled($module);
                }
            } catch (\Throwable) {
                // Panel has no plugin under this id; fall through to config check.
            }
        }

        return in_array($module, (array) config('laravel-crm.modules', []), true);
    }

    /**
     * @deprecated Use moduleEnabled('email-marketing') instead. Preserved for
     *             backwards compatibility with any host that overrode it.
     */
    protected static function campaignsModuleEnabled(): bool
    {
        return static::moduleEnabled('email-marketing');
    }
}
