<?php

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureViewsChart;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureVotesChart;

dataset('feature_chart_widgets', [
    'FeatureVotesChart' => [
        FeatureVotesChart::class,
        'crm_feature_votes',
        'created_at',
        'laravel-crm-filament::labels.sales.votes_over_time',
    ],
    'FeatureViewsChart' => [
        FeatureViewsChart::class,
        'crm_feature_views',
        'viewed_at',
        'laravel-crm-filament::labels.sales.views_over_time',
    ],
]);

it('extends ChartWidget', function (string $class) {
    expect(is_subclass_of($class, ChartWidget::class))->toBeTrue();
})->with('feature_chart_widgets');

it('declares columnSpan = full', function (string $class) {
    $ref = new ReflectionProperty($class, 'columnSpan');
    $ref->setAccessible(true);

    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    expect($ref->getValue($widget))->toBe('full');
})->with('feature_chart_widgets');

it('declares public ?Feature $record = null property', function (string $class) {
    $ref = new ReflectionProperty($class, 'record');

    expect($ref->isPublic())->toBeTrue();
    expect($ref->getType()?->getName())->toBe('VentureDrake\\LaravelCrm\\Models\\Feature');
    expect($ref->getType()?->allowsNull())->toBeTrue();

    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    expect($ref->getValue($widget))->toBeNull();
})->with('feature_chart_widgets');

it('defaults $filter to last_30_days', function (string $class) {
    $ref = new ReflectionProperty($class, 'filter');
    $ref->setAccessible(true);

    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    expect($ref->getValue($widget))->toBe('last_30_days');
})->with('feature_chart_widgets');

it('returns bar type', function (string $class) {
    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();

    $ref = new ReflectionMethod($class, 'getType');
    $ref->setAccessible(true);

    expect($ref->invoke($widget))->toBe('bar');
})->with('feature_chart_widgets');

it('exposes the 13 period filter keys', function (string $class) {
    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();

    $ref = new ReflectionMethod($class, 'getFilters');
    $ref->setAccessible(true);

    $filters = $ref->invoke($widget);

    expect(array_keys($filters))->toBe([
        'today',
        'yesterday',
        'last_7_days',
        'last_30_days',
        'last_90_days',
        'last_365_days',
        'this_month',
        'last_month',
        'this_quarter',
        'last_quarter',
        'this_year',
        'last_year',
        'all_time',
    ]);
})->with('feature_chart_widgets');

it('returns empty data when record is null', function (string $class) {
    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();

    $ref = new ReflectionMethod($class, 'getData');
    $ref->setAccessible(true);

    expect($ref->invoke($widget))->toBe(['datasets' => [], 'labels' => []]);
})->with('feature_chart_widgets');

it('uses its heading translation key', function (string $class, string $table, string $column, string $headingKey) {
    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();

    expect($widget->getHeading())->toBe(__($headingKey));
})->with('feature_chart_widgets');

it('source queries the correct table column for bucketing', function (string $class, string $table, string $column) {
    $source = file_get_contents((new ReflectionClass($class))->getFileName());

    expect($source)
        ->toContain("'feature_id', \$feature->id")
        ->toContain("'$column'")
        ->toContain('whereBetween');
})->with('feature_chart_widgets');

it('does not declare a perf threshold dataset', function (string $class) {
    $source = file_get_contents((new ReflectionClass($class))->getFileName());

    expect($source)
        ->not->toContain('perf_threshold_ms')
        ->not->toContain('performance_threshold')
        ->not->toContain('borderDash');
})->with('feature_chart_widgets');

it('resolves allTimeStart against the same table the chart counts from', function (string $class, string $table, string $column) {
    $source = file_get_contents((new ReflectionClass($class))->getFileName());

    // Find the allTimeStart() method body
    $marker = 'function allTimeStart';
    $pos = strpos($source, $marker);
    expect($pos)->not->toBeFalse();

    // Inside that body should reference the right column via ->min()
    $bodyChunk = substr($source, $pos, 400);
    expect($bodyChunk)->toContain("->min('$column')");
})->with('feature_chart_widgets');

it('produces a single dataset with bucketed counts for a hydrated feature', function (string $class, string $table, string $column) {
    if (! class_exists('VentureDrake\\LaravelCrm\\Models\\Feature')) {
        $this->markTestSkipped('Feature model not installed in this vendor build.');
    }

    // Seed a Feature row via raw DB insert (Feature model has Factory and observer hooks,
    // but we just need a real id to query against).
    $featureId = DB::table('crm_features')->insertGetId([
        'external_id' => (string) Str::uuid(),
        'title' => 'Test',
        'votes_count' => 0,
        'comments_count' => 0,
        'views_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Seed three rows in the chart's source table all inside the last_30_days range.
    $today = today()->startOfDay();
    $base = ['feature_id' => $featureId];
    if ($table === 'crm_feature_votes') {
        $rows = [
            $base + ['user_id' => 1, 'created_at' => $today->copy()->subDays(2), 'updated_at' => $today->copy()->subDays(2)],
            $base + ['user_id' => 2, 'created_at' => $today->copy()->subDays(2), 'updated_at' => $today->copy()->subDays(2)],
            $base + ['user_id' => 3, 'created_at' => $today->copy()->subDays(5), 'updated_at' => $today->copy()->subDays(5)],
        ];
    } else {
        $rows = [
            $base + ['viewed_at' => $today->copy()->subDays(2)],
            $base + ['viewed_at' => $today->copy()->subDays(2)],
            $base + ['viewed_at' => $today->copy()->subDays(5)],
        ];
    }
    DB::table($table)->insert($rows);

    // Hydrate a Feature record and exercise getData()
    $featureClass = 'VentureDrake\\LaravelCrm\\Models\\Feature';
    $feature = $featureClass::find($featureId);

    $widget = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    $widget->record = $feature;

    $ref = new ReflectionMethod($class, 'getData');
    $ref->setAccessible(true);

    $data = $ref->invoke($widget);

    expect($data)->toHaveKeys(['datasets', 'labels']);
    expect($data['datasets'])->toHaveCount(1);
    expect($data['labels'])->not->toBeEmpty();

    // Sum of bucket counts across all visible buckets should equal the 3 seeded rows.
    $total = array_sum($data['datasets'][0]['data']);
    expect($total)->toBe(3);
})->with('feature_chart_widgets');

it('imports Carbon, ChartWidget, and Feature', function (string $class) {
    $source = file_get_contents((new ReflectionClass($class))->getFileName());

    expect($source)
        ->toContain('use Carbon\\Carbon;')
        ->toContain('use Carbon\\CarbonInterface;')
        ->toContain('use Filament\\Widgets\\ChartWidget;')
        ->toContain('use VentureDrake\\LaravelCrm\\Models\\Feature;');
})->with('feature_chart_widgets');

it('lives under the VentureDrake\\LaravelCrmFilament\\Widgets namespace', function (string $class) {
    $ref = new ReflectionClass($class);
    expect($ref->getNamespaceName())->toBe('VentureDrake\\LaravelCrmFilament\\Widgets');
})->with('feature_chart_widgets');
