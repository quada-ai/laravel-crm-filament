<?php

use Filament\Facades\Filament;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\FeatureStatusResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\Pages\CreateFeatureStatus;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\Pages\EditFeatureStatus;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FeatureStatuses\Pages\ListFeatureStatuses;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

it('binds FeatureStatusResource to the FeatureStatus model and lives in the Settings cluster', function () {
    expect(FeatureStatusResource::getModel())->toBe('VentureDrake\\LaravelCrm\\Models\\FeatureStatus');
    expect(FeatureStatusResource::getCluster())->toBe(Settings::class);
    expect(FeatureStatusResource::getRecordRouteKeyName())->toBe('external_id');
});

it('uses the UsesExternalIdRouting trait', function () {
    expect(class_uses_recursive(FeatureStatusResource::class))
        ->toContain(UsesExternalIdRouting::class);
});

it('declares feature-statuses as the slug', function () {
    expect(FeatureStatusResource::getSlug())->toBe('feature-statuses');
});

it('exposes list+create+edit pages on FeatureStatusResource', function () {
    expect(array_keys(FeatureStatusResource::getPages()))
        ->toEqual(['index', 'create', 'edit']);
});

it('routes FeatureStatus page classes back to FeatureStatusResource', function () {
    foreach ([CreateFeatureStatus::class, EditFeatureStatus::class, ListFeatureStatuses::class] as $page) {
        $reflection = new ReflectionProperty($page, 'resource');
        $reflection->setAccessible(true);
        expect($reflection->getValue())->toBe(FeatureStatusResource::class);
    }
});

it('declares name + order + ColorPicker + description + is_default + is_closed in the form', function () {
    $source = file_get_contents((new ReflectionClass(FeatureStatusResource::class))->getFileName());

    expect($source)->toContain("Forms\\Components\\TextInput::make('name')");
    expect($source)->toContain("Forms\\Components\\TextInput::make('order')");
    expect($source)->toContain("Forms\\Components\\ColorPicker::make('color')");
    expect($source)->toContain("Forms\\Components\\Textarea::make('description')");
    expect($source)->toContain("Forms\\Components\\Toggle::make('is_default')");
    expect($source)->toContain("Forms\\Components\\Toggle::make('is_closed')");
});

it('renders name + color swatch + order + is_default + is_closed + features_count columns', function () {
    $source = file_get_contents((new ReflectionClass(FeatureStatusResource::class))->getFileName());

    expect($source)->toContain("Tables\\Columns\\TextColumn::make('name')");
    expect($source)->toContain("Tables\\Columns\\ColorColumn::make('color')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('order')");
    expect($source)->toContain("Tables\\Columns\\IconColumn::make('is_default')");
    expect($source)->toContain("Tables\\Columns\\IconColumn::make('is_closed')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('features_count')");
});

it('makes the order column sortable in source', function () {
    $source = file_get_contents((new ReflectionClass(FeatureStatusResource::class))->getFileName());
    // order is the third column and ->sortable() is chained on its TextColumn::make
    expect($source)->toMatch("/TextColumn::make\\('order'\\)->sortable\\(\\)/");
});

it('eager-loads features_count via withCount on the resource query', function () {
    $source = file_get_contents((new ReflectionClass(FeatureStatusResource::class))->getFileName());
    expect($source)->toContain("withCount('features')");
});

it('declares is_default and is_closed as boolean IconColumns', function () {
    $source = file_get_contents((new ReflectionClass(FeatureStatusResource::class))->getFileName());

    // Both is_default and is_closed should be IconColumns followed by ->boolean()
    expect($source)->toMatch("/IconColumn::make\\('is_default'\\)[\\s\\S]*?->boolean\\(\\)/");
    expect($source)->toMatch("/IconColumn::make\\('is_closed'\\)[\\s\\S]*?->boolean\\(\\)/");
});

it('registers FeatureStatusResource on the panel regardless of module flags', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(FeatureStatusResource::class);
});

it('does not gate FeatureStatusResource on a module flag in LaravelCrmPlugin source', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());
    // The registration line should sit in the "always-registered" Settings cluster block,
    // not inside an isModuleEnabled() conditional.
    expect($source)->toMatch("/\\\$resources\\[\\] = FeatureStatusResource::class;/");

    // Locate FeatureStatusResource registration and assert no nearby isModuleEnabled guard wraps it.
    $needle = '$resources[] = FeatureStatusResource::class;';
    $pos = strpos($source, $needle);
    expect($pos)->not->toBeFalse();

    // Pull a 600-char window leading up to the registration; ensure the most-recent
    // control flow is the "Settings cluster lookups — always available" comment block.
    $window = substr($source, max(0, $pos - 600), 600);
    expect($window)->toContain('Settings cluster lookups');
});
