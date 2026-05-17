<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('resolves the lead resource to the Lead model', function () {
    expect(LeadResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Lead::class);
});

it('routes lead records by external_id', function () {
    expect(LeadResource::getRecordRouteKeyName())->toBe('external_id');
});

it('respects the modules() override on the plugin', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['leads' => false, 'deals' => true]);

    expect($plugin->isModuleEnabled('leads'))->toBeFalse();
    expect($plugin->isModuleEnabled('deals'))->toBeTrue();
});

