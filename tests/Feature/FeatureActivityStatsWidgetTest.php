<?php

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrmFilament\Widgets\FeatureActivityStatsWidget;

it('extends StatsOverviewWidget', function () {
    expect(is_subclass_of(FeatureActivityStatsWidget::class, StatsOverviewWidget::class))->toBeTrue();
});

it('declares columnSpan = full', function () {
    $ref = new ReflectionProperty(FeatureActivityStatsWidget::class, 'columnSpan');
    $ref->setAccessible(true);

    $widget = (new ReflectionClass(FeatureActivityStatsWidget::class))->newInstanceWithoutConstructor();
    expect($ref->getValue($widget))->toBe('full');
});

it('declares public ?Feature $record = null property', function () {
    $ref = new ReflectionProperty(FeatureActivityStatsWidget::class, 'record');

    expect($ref->isPublic())->toBeTrue();
    expect($ref->getType()?->getName())->toBe('VentureDrake\\LaravelCrm\\Models\\Feature');
    expect($ref->getType()?->allowsNull())->toBeTrue();

    $widget = (new ReflectionClass(FeatureActivityStatsWidget::class))->newInstanceWithoutConstructor();
    expect($ref->getValue($widget))->toBeNull();
});

it('getColumns() returns 3', function () {
    $widget = (new ReflectionClass(FeatureActivityStatsWidget::class))->newInstanceWithoutConstructor();

    $ref = new ReflectionMethod(FeatureActivityStatsWidget::class, 'getColumns');
    $ref->setAccessible(true);

    expect($ref->invoke($widget))->toBe(3);
});

it('getStats() returns exactly 3 Stat instances in Votes / Comments / Views order with em-dashes when record is null', function () {
    $widget = (new ReflectionClass(FeatureActivityStatsWidget::class))->newInstanceWithoutConstructor();

    $ref = new ReflectionMethod(FeatureActivityStatsWidget::class, 'getStats');
    $ref->setAccessible(true);

    $stats = $ref->invoke($widget);

    expect($stats)->toHaveCount(3);
    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(Stat::class);
    }

    // Labels in order: Votes / Comments / Views (resolved at construction time)
    expect($stats[0]->getLabel())->toBe('Votes');
    expect($stats[1]->getLabel())->toBe('Comments');
    expect($stats[2]->getLabel())->toBe('Views');

    // Null record → em-dash placeholders
    expect($stats[0]->getValue())->toBe('—');
    expect($stats[1]->getValue())->toBe('—');
    expect($stats[2]->getValue())->toBe('—');
});

it('getStats() renders the votes/comments/views counts when record is hydrated', function () {
    if (! class_exists('VentureDrake\\LaravelCrm\\Models\\Feature')) {
        $this->markTestSkipped('Feature model not installed in this vendor build.');
    }

    $featureClass = 'VentureDrake\\LaravelCrm\\Models\\Feature';
    $feature = new $featureClass;
    $feature->votes_count = 12;
    $feature->comments_count = 5;
    $feature->views_count = 200;

    $widget = (new ReflectionClass(FeatureActivityStatsWidget::class))->newInstanceWithoutConstructor();
    $widget->record = $feature;

    $ref = new ReflectionMethod(FeatureActivityStatsWidget::class, 'getStats');
    $ref->setAccessible(true);

    $stats = $ref->invoke($widget);

    expect($stats[0]->getValue())->toBe('12');
    expect($stats[1]->getValue())->toBe('5');
    expect($stats[2]->getValue())->toBe('200');
});

it('source references the three labels.fields.{votes,comments,views} translation keys', function () {
    $source = file_get_contents((new ReflectionClass(FeatureActivityStatsWidget::class))->getFileName());

    expect($source)->toContain('laravel-crm-filament::labels.fields.votes');
    expect($source)->toContain('laravel-crm-filament::labels.fields.comments');
    expect($source)->toContain('laravel-crm-filament::labels.fields.views');
});

it('imports Feature, StatsOverviewWidget, and Stat at the top of the source', function () {
    $source = file_get_contents((new ReflectionClass(FeatureActivityStatsWidget::class))->getFileName());

    expect($source)->toContain('use VentureDrake\\LaravelCrm\\Models\\Feature;');
    expect($source)->toContain('use Filament\\Widgets\\StatsOverviewWidget;');
    expect($source)->toContain('use Filament\\Widgets\\StatsOverviewWidget\\Stat;');
});

it('lives under the VentureDrake\\LaravelCrmFilament\\Widgets namespace', function () {
    $ref = new ReflectionClass(FeatureActivityStatsWidget::class);
    expect($ref->getNamespaceName())->toBe('VentureDrake\\LaravelCrmFilament\\Widgets');
});
