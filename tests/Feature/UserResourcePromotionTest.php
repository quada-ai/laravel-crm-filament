<?php

use Filament\Facades\Filament;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\CreateUser;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\EditUser;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ListUsers;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;

it('lives under the top-level Resources namespace, not the Settings cluster namespace', function () {
    expect(UserResource::class)->toStartWith('VentureDrake\\LaravelCrmFilament\\Resources\\Users\\');
    expect(class_exists('VentureDrake\\LaravelCrmFilament\\Clusters\\Settings\\Resources\\Users\\UserResource'))->toBeFalse();
});

it('no longer declares the Settings cluster on UserResource', function () {
    expect(UserResource::getCluster())->toBeNull();
});

it('declares Contacts as the navigation group on UserResource', function () {
    $reflection = new ReflectionProperty(UserResource::class, 'navigationGroup');
    $reflection->setAccessible(true);
    expect($reflection->getValue())->toBe('Contacts');
});

it('declares navigationSort=50 on UserResource', function () {
    $reflection = new ReflectionProperty(UserResource::class, 'navigationSort');
    $reflection->setAccessible(true);
    expect($reflection->getValue())->toBe(50);
});

it('routes its three pages under the new namespace', function () {
    expect(array_keys(UserResource::getPages()))->toEqual(['index', 'create', 'view', 'edit']);
    foreach ([CreateUser::class, EditUser::class, ListUsers::class] as $page) {
        expect($page)->toStartWith('VentureDrake\\LaravelCrmFilament\\Resources\\Users\\Pages\\');
        $reflection = new ReflectionProperty($page, 'resource');
        $reflection->setAccessible(true);
        expect($reflection->getValue())->toBe(UserResource::class);
    }
});

it('registers UserResource on the panel as a top-level resource', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(UserResource::class);
});

it('Settings cluster resources dataset does not include UserResource', function () {
    // SettingsClusterTest's `settingsResources` dataset is the source of truth for
    // which resources live in the Settings cluster. UserResource MUST NOT appear
    // there after the promotion.
    $file = dirname(__DIR__) . '/Feature/SettingsClusterTest.php';
    $src = file_get_contents($file);
    expect($src)->not->toContain('UserResource');
});
