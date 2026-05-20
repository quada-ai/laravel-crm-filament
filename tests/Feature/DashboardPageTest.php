<?php

use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Pages\Dashboard;
use VentureDrake\LaravelCrmFilament\Widgets\CampaignPerformanceChart;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;

it('plugin Dashboard page extends the Filament Dashboard base', function () {
    expect(is_subclass_of(Dashboard::class, BaseDashboard::class))->toBeTrue();
});

it('registers the plugin Dashboard page on a fresh panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('dashboard-default-' . uniqid());
    $plugin->register($panel);

    expect($panel->getPages())->toContain(Dashboard::class);
});

it('returns the six core widgets from getWidgets() when campaigns are disabled', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['email-marketing' => false]);
    $panel = Panel::make()->id('dashboard-no-campaigns-' . uniqid());
    $plugin->register($panel);

    Filament::setCurrentPanel($panel);

    try {
        $widgets = (new Dashboard)->getWidgets();
    } finally {
        Filament::setCurrentPanel(null);
    }

    expect($widgets)->toBe([
        CrmStatsOverview::class,
        DealsValueStat::class,
        LeadsByStageChart::class,
        MonthlyRevenueChart::class,
        TasksDueTodayList::class,
        RecentActivityList::class,
    ]);
});

it('includes CampaignPerformanceChart in getWidgets() when campaigns are enabled', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['email-marketing' => true]);
    $panel = Panel::make()->id('dashboard-with-campaigns-' . uniqid());
    $plugin->register($panel);

    Filament::setCurrentPanel($panel);

    try {
        $widgets = (new Dashboard)->getWidgets();
    } finally {
        Filament::setCurrentPanel(null);
    }

    expect($widgets)->toContain(CampaignPerformanceChart::class);
    expect(end($widgets))->toBe(CampaignPerformanceChart::class);
});

it('omits CampaignPerformanceChart from getWidgets() when campaigns are disabled', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['email-marketing' => false]);
    $panel = Panel::make()->id('dashboard-omit-campaigns-' . uniqid());
    $plugin->register($panel);

    Filament::setCurrentPanel($panel);

    try {
        $widgets = (new Dashboard)->getWidgets();
    } finally {
        Filament::setCurrentPanel(null);
    }

    expect($widgets)->not->toContain(CampaignPerformanceChart::class);
});

it('does not register the plugin Dashboard when the host has already registered one', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('dashboard-host-wins-' . uniqid());

    // Host registers Filament's base Dashboard before the plugin runs.
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
