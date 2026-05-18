<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

it('honours every module override on the plugin', function (string $module, bool $enabled) {
    $plugin = LaravelCrmPlugin::make()->modules([$module => $enabled]);
    expect($plugin->isModuleEnabled($module))->toBe($enabled);
})->with([
    ['leads', true], ['leads', false],
    ['deals', true], ['deals', false],
    ['quotes', true], ['quotes', false],
    ['orders', true], ['orders', false],
    ['invoices', true], ['invoices', false],
    ['deliveries', true], ['deliveries', false],
    ['purchase-orders', true], ['purchase-orders', false],
    ['email-marketing', true], ['email-marketing', false],
    ['sms-marketing', true], ['sms-marketing', false],
    ['chat', true], ['chat', false],
]);

it('exposes branding fluent setters that chain', function () {
    $plugin = LaravelCrmPlugin::make()
        ->brand('Acme CRM')
        ->brandLogo('https://example.com/logo.svg')
        ->favicon('https://example.com/favicon.ico')
        ->primaryColor('#FF5733');

    expect($plugin)->toBeInstanceOf(LaravelCrmPlugin::class);
    expect($plugin->getBrand())->toBe('Acme CRM');
});

it('exposes navigationGroup() as a chainable setter', function () {
    $plugin = LaravelCrmPlugin::make()->navigationGroup('CRM');
    expect($plugin->getNavigationGroup())->toBe('CRM');
});
