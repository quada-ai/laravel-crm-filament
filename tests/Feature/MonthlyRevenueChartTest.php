<?php

use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrmFilament\Concerns\HasChartRangeFilter;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;

it('extends ChartWidget and uses HasChartRangeFilter', function () {
    expect(is_subclass_of(MonthlyRevenueChart::class, ChartWidget::class))->toBeTrue();
    expect(class_uses_recursive(MonthlyRevenueChart::class))->toContain(HasChartRangeFilter::class);
});

it('declares full column span', function () {
    $ref = new ReflectionProperty(MonthlyRevenueChart::class, 'columnSpan');
    $ref->setAccessible(true);
    expect($ref->getValue(new MonthlyRevenueChart))->toBe('full');
});

it('returns a line chart type', function () {
    $method = new ReflectionMethod(MonthlyRevenueChart::class, 'getType');
    $method->setAccessible(true);
    expect($method->invoke(new MonthlyRevenueChart))->toBe('line');
});

it('defaults the period filter to last_30_days', function () {
    $widget = new MonthlyRevenueChart;
    expect($widget->filter)->toBe('last_30_days');
});

it('getFilters() returns the shared 13-key period list from the trait', function () {
    $method = new ReflectionMethod(MonthlyRevenueChart::class, 'getFilters');
    $method->setAccessible(true);
    $filters = $method->invoke(new MonthlyRevenueChart);

    expect(array_keys($filters))->toBe([
        'today', 'yesterday', 'last_7_days', 'last_30_days', 'last_90_days', 'last_365_days',
        'this_month', 'last_month', 'this_quarter', 'last_quarter', 'this_year', 'last_year', 'all_time',
    ]);
});

it('getHeading() resolves the dashboard.revenue_trend translation key', function () {
    $widget = new MonthlyRevenueChart;
    expect($widget->getHeading())->toBe(__('laravel-crm-filament::labels.dashboard.revenue_trend'));
});

it('getData() produces two datasets labelled paid_invoices and orders', function () {
    $method = new ReflectionMethod(MonthlyRevenueChart::class, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke(new MonthlyRevenueChart);

    expect($data)->toHaveKey('datasets');
    expect($data['datasets'])->toHaveCount(2);
    expect($data['datasets'][0]['label'])->toBe(__('laravel-crm-filament::labels.dashboard.paid_invoices'));
    expect($data['datasets'][1]['label'])->toBe(__('laravel-crm-filament::labels.dashboard.orders'));
});

it('sums paid invoices by fully_paid_at and orders by created_at into the same buckets', function () {
    $today = today();

    Invoice::create([
        'total' => 10000, // 100.00 stored as cents (setTotal multiplies by 100 at write time)
        'fully_paid_at' => $today->copy()->subDays(2),
    ]);

    Invoice::create([
        'total' => 5000,
        'fully_paid_at' => $today->copy()->subDays(5),
    ]);

    // Invoice not yet paid — must be excluded.
    Invoice::create([
        'total' => 99999,
        'fully_paid_at' => null,
    ]);

    Order::create([
        'total' => 20000,
        'created_at' => $today->copy()->subDays(2),
    ]);

    Order::create([
        'total' => 30000,
        'created_at' => $today->copy()->subDays(20),
    ]);

    $method = new ReflectionMethod(MonthlyRevenueChart::class, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke(new MonthlyRevenueChart);

    $invoiceTotal = array_sum($data['datasets'][0]['data']);
    $orderTotal = array_sum($data['datasets'][1]['data']);

    // Amounts are stored in cents by the model setters (×100); the chart divides by 100 back to dollar units.
    // Invoice::create(['total' => 10000]) writes 1_000_000 cents = $10,000; getData divides → 10000.0.
    expect($invoiceTotal)->toEqual(10000.0 + 5000.0);
    expect($orderTotal)->toEqual(20000.0 + 30000.0);
});

it('excludes invoices whose fully_paid_at falls outside the window', function () {
    Invoice::create([
        'total' => 12345,
        'fully_paid_at' => today()->copy()->subYear(),
    ]);

    $method = new ReflectionMethod(MonthlyRevenueChart::class, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke(new MonthlyRevenueChart);

    expect(array_sum($data['datasets'][0]['data']))->toEqual(0.0);
});
