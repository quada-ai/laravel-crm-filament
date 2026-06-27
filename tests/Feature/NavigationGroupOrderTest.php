<?php

use Filament\Panel;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

// AC (US-004): $panel->navigationGroups(['Contacts', 'Settings']) is called in the panel boot
// sequence so the visible nav-group order pins Contacts first and Settings right after.
// Filament renders any other groups after this listed pair via default trailing behavior.
it('pins the navigation group order to [Contacts, Settings] on the panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('us004-nav-group-order')->default();
    $plugin->register($panel);

    $groups = $panel->getNavigationGroups();

    expect($groups)->toBeArray();
    expect(array_values($groups))->toBe(['Contacts', 'Settings']);
});

it('declares the navigationGroups call in LaravelCrmPlugin source', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->toContain("\$panel->navigationGroups(['Contacts', 'Settings'])");
});

it('does not call discoverClusters in LaravelCrmPlugin source (regression guard)', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->not->toContain('discoverClusters');
});
