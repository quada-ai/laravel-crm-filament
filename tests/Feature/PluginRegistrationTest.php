<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmFilamentServiceProvider;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

it('registers the service provider', function () {
    expect(app()->getProvider(LaravelCrmFilamentServiceProvider::class))->not->toBeNull();
});

it('exposes a plugin with the laravel-crm id', function () {
    expect(LaravelCrmPlugin::make()->getId())->toBe('laravel-crm');
});

