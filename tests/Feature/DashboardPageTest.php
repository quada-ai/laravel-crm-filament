<?php

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Pages\Dashboard;
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

/**
 * Helper: register the plugin against a fresh panel, set that panel as
 * the current one for the duration of the callback, and return the
 * callback's result. Guarantees the panel context is torn down after each
 * scenario so tests don't leak state.
 */
function us008WithPanel(array $modules, callable $fn)
{
    $plugin = LaravelCrmPlugin::make()->modules($modules);
    $panel = Panel::make()->id('us008-' . uniqid());
    // Attach the plugin to the panel's plugin registry so
    // $panel->getPlugin('laravel-crm') resolves at runtime.
    $panel->plugin($plugin);
    $plugin->register($panel);
    Filament::setCurrentPanel($panel);

    try {
        return $fn($panel);
    } finally {
        Filament::setCurrentPanel(null);
    }
}

/**
 * Normalise Dashboard::getWidgets() output — chart widgets are wrapped in
 * Widget::make(['columnSpan' => 1]) which returns a WidgetConfiguration
 * object. Flatten both shapes to a plain array of class-string values so
 * ->toContain(Class::class) and ->toBe([...]) assertions read cleanly.
 */
function us008WidgetClasses(array $widgets): array
{
    return array_map(
        fn ($entry) => is_string($entry) ? $entry : $entry->widget,
        $widgets,
    );
}

it('plugin Dashboard page extends the Filament Dashboard base', function () {
    expect(is_subclass_of(Dashboard::class, BaseDashboard::class))->toBeTrue();
});

it('registers the plugin Dashboard page on a fresh panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('dashboard-default-' . uniqid());
    $plugin->register($panel);

    expect($panel->getPages())->toContain(Dashboard::class);
});

it('returns the full 11-widget layout in AC order when every gated module is enabled', function () {
    $widgets = us008WithPanel([
        'leads' => true,
        'deals' => true,
        'quotes' => true,
        'orders' => true,
        'invoices' => true,
        'email-marketing' => true,
    ], fn () => (new Dashboard)->getWidgets());
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->toBe([
        CrmStatsOverview::class,
        DealsValueStat::class,
        ContactsStatsOverview::class,
        MonthlyRevenueChart::class,
        DealsPipelineValueChart::class,
        LeadsVsDealsChart::class,
        DealStatusDoughnutChart::class,
        LeadsByStageChart::class,
        TasksDueTodayList::class,
        RecentActivityList::class,
        CampaignPerformanceChart::class,
    ]);
});

it('surfaces only the ungated widgets when every module is disabled', function () {
    $widgets = us008WithPanel([
        'leads' => false,
        'deals' => false,
        'quotes' => false,
        'orders' => false,
        'invoices' => false,
        'email-marketing' => false,
    ], fn () => (new Dashboard)->getWidgets());
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->toBe([
        ContactsStatsOverview::class,
        TasksDueTodayList::class,
        RecentActivityList::class,
    ]);
});

it('drops leads-gated widgets when the leads module is disabled', function () {
    $widgets = us008WithPanel([
        'leads' => false,
        'deals' => true,
        'quotes' => true,
        'orders' => true,
        'invoices' => true,
        'email-marketing' => true,
    ], fn () => (new Dashboard)->getWidgets());
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->not->toContain(LeadsByStageChart::class);
    // CrmStatsOverview + LeadsVsDealsChart stay because deals=true satisfies their OR gate.
    expect($widgets)->toContain(CrmStatsOverview::class);
    expect($widgets)->toContain(LeadsVsDealsChart::class);
});

it('drops deals-gated widgets when the deals module is disabled', function () {
    $widgets = us008WithPanel([
        'leads' => true,
        'deals' => false,
        'quotes' => true,
        'orders' => true,
        'invoices' => true,
        'email-marketing' => true,
    ], fn () => (new Dashboard)->getWidgets());
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->not->toContain(DealsPipelineValueChart::class);
    expect($widgets)->not->toContain(DealStatusDoughnutChart::class);
    // CrmStatsOverview + LeadsVsDealsChart stay because leads=true satisfies their OR gate.
    expect($widgets)->toContain(CrmStatsOverview::class);
    expect($widgets)->toContain(LeadsVsDealsChart::class);
});

it('drops Revenue Trend when invoices AND orders are both disabled', function () {
    $widgets = us008WithPanel([
        'leads' => true,
        'deals' => true,
        'quotes' => true,
        'orders' => false,
        'invoices' => false,
        'email-marketing' => true,
    ], fn () => (new Dashboard)->getWidgets());
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->not->toContain(MonthlyRevenueChart::class);
});

it('surfaces Finance stats when any of quotes/orders/invoices is enabled', function () {
    // Only quotes enabled — the widget still surfaces.
    $widgetsQuotes = us008WithPanel([
        'quotes' => true, 'orders' => false, 'invoices' => false,
    ], fn () => (new Dashboard)->getWidgets());
    $widgetsQuotes = us008WidgetClasses($widgetsQuotes);
    expect($widgetsQuotes)->toContain(DealsValueStat::class);

    // Fully disabled — the widget is absent.
    $widgetsNone = us008WithPanel([
        'quotes' => false, 'orders' => false, 'invoices' => false,
    ], fn () => (new Dashboard)->getWidgets());
    $widgetsNone = us008WidgetClasses($widgetsNone);
    expect($widgetsNone)->not->toContain(DealsValueStat::class);
});

it('ContactsStatsOverview, TasksDueTodayList and RecentActivityList are always present regardless of modules', function () {
    foreach ([
        ['leads' => true, 'deals' => true, 'quotes' => true, 'orders' => true, 'invoices' => true, 'email-marketing' => true],
        ['leads' => false, 'deals' => false, 'quotes' => false, 'orders' => false, 'invoices' => false, 'email-marketing' => false],
    ] as $modules) {
        $widgets = us008WithPanel($modules, fn () => (new Dashboard)->getWidgets());
        $widgets = us008WidgetClasses($widgets);

        expect($widgets)->toContain(ContactsStatsOverview::class)
            ->toContain(TasksDueTodayList::class)
            ->toContain(RecentActivityList::class);
    }
});

it('includes CampaignPerformanceChart last when email-marketing is enabled', function () {
    $widgets = us008WithPanel(
        ['email-marketing' => true, 'leads' => true, 'deals' => true],
        fn () => (new Dashboard)->getWidgets()
    );
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->toContain(CampaignPerformanceChart::class);
    expect(end($widgets))->toBe(CampaignPerformanceChart::class);
});

it('omits CampaignPerformanceChart when email-marketing is disabled', function () {
    $widgets = us008WithPanel(
        ['email-marketing' => false, 'leads' => true, 'deals' => true],
        fn () => (new Dashboard)->getWidgets()
    );
    $widgets = us008WidgetClasses($widgets);

    expect($widgets)->not->toContain(CampaignPerformanceChart::class);
});

it('LaravelCrmPlugin::register() adds the four new widget FQCNs to the panel widget list', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('us008-plugin-widgets-' . uniqid());
    $plugin->register($panel);

    $widgets = $panel->getWidgets();

    expect($widgets)->toContain(ContactsStatsOverview::class)
        ->toContain(LeadsVsDealsChart::class)
        ->toContain(DealsPipelineValueChart::class)
        ->toContain(DealStatusDoughnutChart::class);
});

it('does not register the plugin Dashboard when the host has already registered one', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('dashboard-host-wins-' . uniqid());

    $panel->pages([BaseDashboard::class]);

    $plugin->register($panel);

    $pages = $panel->getPages();
    expect($pages)->toContain(BaseDashboard::class);
    expect($pages)->not->toContain(Dashboard::class);
});

it('does not register the plugin Dashboard when the host has registered a Dashboard subclass', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('dashboard-host-subclass-' . uniqid());

    $hostDashboard = new class extends BaseDashboard {};
    $panel->pages([$hostDashboard::class]);

    $plugin->register($panel);

    expect($panel->getPages())->not->toContain(Dashboard::class);
});

it('allows hosts to opt out of the plugin Dashboard via withDashboard(false)', function () {
    $plugin = LaravelCrmPlugin::make()->withDashboard(false);
    $panel = Panel::make()->id('dashboard-opt-out-' . uniqid());
    $plugin->register($panel);

    expect($panel->getPages())->not->toContain(Dashboard::class);
});

it('exposes moduleEnabled() as a protected static helper that resolves module state via the current panel plugin', function () {
    $reflection = new ReflectionMethod(Dashboard::class, 'moduleEnabled');

    expect($reflection->isProtected())->toBeTrue()
        ->and($reflection->isStatic())->toBeTrue()
        ->and($reflection->getNumberOfRequiredParameters())->toBe(1);

    // Runtime — returns true when the plugin says so, false when it doesn't.
    $onResult = us008WithPanel(['leads' => true], function () use ($reflection) {
        return $reflection->invoke(null, 'leads');
    });
    $offResult = us008WithPanel(['leads' => false], function () use ($reflection) {
        return $reflection->invoke(null, 'leads');
    });

    expect($onResult)->toBeTrue()
        ->and($offResult)->toBeFalse();
});
