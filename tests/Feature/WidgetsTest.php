<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;

dataset('widgets', [
    'CrmStatsOverview' => [CrmStatsOverview::class, \Filament\Widgets\StatsOverviewWidget::class],
    'DealsValueStat' => [DealsValueStat::class, \Filament\Widgets\StatsOverviewWidget::class],
    'LeadsByStageChart' => [LeadsByStageChart::class, \Filament\Widgets\ChartWidget::class],
    'MonthlyRevenueChart' => [MonthlyRevenueChart::class, \Filament\Widgets\ChartWidget::class],
    'TasksDueTodayList' => [TasksDueTodayList::class, \Filament\Widgets\TableWidget::class],
    'RecentActivityList' => [RecentActivityList::class, \Filament\Widgets\TableWidget::class],
]);

it('each dashboard widget extends the expected Filament widget base', function (string $widget, string $base) {
    expect(is_subclass_of($widget, $base))->toBeTrue();
})->with('widgets');

it('table widgets declare a heading static property', function () {
    foreach ([TasksDueTodayList::class, RecentActivityList::class] as $widget) {
        $reflection = new ReflectionClass($widget);
        expect($reflection->getStaticPropertyValue('heading'))
            ->toBeString()
            ->not->toBeEmpty();
    }
});

it('stats widgets declare a non-empty heading', function () {
    foreach ([CrmStatsOverview::class, DealsValueStat::class] as $widget) {
        $instance = new $widget;
        $heading = (new ReflectionClass($widget))->getProperty('heading');
        $heading->setAccessible(true);
        expect($heading->getValue($instance))->toBeString()->not->toBeEmpty();
    }
});
