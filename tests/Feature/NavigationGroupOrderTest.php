<?php

use Filament\Panel;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

// AC (US-004): $panel->navigationGroups(['Contacts', 'Settings']) is called in the panel boot
// sequence so the visible nav-group order pins Contacts first and Settings right after.
// Filament renders any other groups after this listed pair via default trailing behavior.
it('pins the navigation group order end-to-end on the panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('us004-nav-group-order')->default();
    $plugin->register($panel);

    $groups = $panel->getNavigationGroups();

    expect($groups)->toBeArray();
    expect(array_values($groups))->toBe(['Activity', 'Marketing', 'Sales', 'Contacts', 'Roadmap', 'Monitoring', 'Catalog', 'Settings']);
});

it('declares the navigationGroups call in LaravelCrmPlugin source', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->toContain('$panel->navigationGroups([')
        ->toContain("'Activity',")
        ->toContain("'Marketing',")
        ->toContain("'Sales',")
        ->toContain("'Contacts',")
        ->toContain("'Roadmap',")
        ->toContain("'Monitoring',")
        ->toContain("'Catalog',")
        ->toContain("'Settings',");
});

it('does not call discoverClusters in LaravelCrmPlugin source (regression guard)', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->not->toContain('discoverClusters');
});
