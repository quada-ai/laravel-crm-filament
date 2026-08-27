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
    $labels = array_map(
        fn ($group) => $group instanceof \Filament\Navigation\NavigationGroup ? $group->getLabel() : $group,
        array_values($groups),
    );
    expect($labels)->toBe(['Sales', 'Contacts', 'Activity', 'Marketing', 'Catalog', 'Monitoring', 'Roadmap', 'Integrations', 'Settings']);
});

it('resolves translated navigation group order when switching to arabic locale', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Panel::make()->id('nav-group-order-ar')->default();
    $plugin->register($panel);

    app()->setLocale('ar');

    $groups = $panel->getNavigationGroups();
    $labels = array_map(
        fn ($group) => $group instanceof \Filament\Navigation\NavigationGroup ? $group->getLabel() : $group,
        array_values($groups),
    );

    expect($labels)->toBe(['المبيعات', 'جهات الاتصال', 'النشاط', 'التسويق', 'الكتالوج', 'المراقبة', 'خارطة الطريق', 'التكاملات', 'الإعدادات']);
});

it('declares the navigationGroups call in LaravelCrmPlugin source', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->toContain('$panel->navigationGroups([')
        ->toContain('navigation.groups.activity')
        ->toContain('navigation.groups.marketing')
        ->toContain('navigation.groups.sales')
        ->toContain('navigation.groups.contacts')
        ->toContain('navigation.groups.roadmap')
        ->toContain('navigation.groups.monitoring')
        ->toContain('navigation.groups.catalog')
        ->toContain('navigation.groups.settings');
});

it('does not call discoverClusters in LaravelCrmPlugin source (regression guard)', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmPlugin::class))->getFileName());

    expect($source)->not->toContain('discoverClusters');
});
