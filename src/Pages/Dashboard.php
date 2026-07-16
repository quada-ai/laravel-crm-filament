<?php

namespace VentureDrake\LaravelCrmFilament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Widgets\ContactsStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsPipelineValueChart;
use VentureDrake\LaravelCrmFilament\Widgets\DealStatusDoughnutChart;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsVsDealsChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;

class Dashboard extends BaseDashboard
{
    /**
     * Layout mirrors the core `laravel-crm` package's /crm/dashboard exactly:
     *   Sales stats → Finance stats → Contacts stat → Revenue Trend →
     *   Pipeline Value → Leads vs Deals → Deal Status →
     *   Upcoming Tasks → Recent Activity.
     *
     * Widgets not present in core (LeadsByStageChart, CampaignPerformanceChart)
     * are intentionally omitted so the Filament dashboard stays feature-parity with
     * the Livewire dashboard.
     *
     * ContactsStatsOverview, TasksDueTodayList, RecentActivityList are ungated.
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
            $widgets[] = MonthlyRevenueChart::class;
        }

        // Pipeline Value / Deal Status are deal-only.
        if (static::moduleEnabled('deals')) {
            $widgets[] = DealsPipelineValueChart::class;
        }

        // Leads vs Deals needs at least one of the two modules.
        if (static::moduleEnabled('leads') || static::moduleEnabled('deals')) {
            $widgets[] = LeadsVsDealsChart::class;
        }

        if (static::moduleEnabled('deals')) {
            $widgets[] = DealStatusDoughnutChart::class;
        }

        // Upcoming Tasks + Recent Activity — ungated per AC.
        $widgets[] = TasksDueTodayList::class;
        $widgets[] = RecentActivityList::class;

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
