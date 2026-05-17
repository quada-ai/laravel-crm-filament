<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

it('resolves the quote resource to the Quote model', function () {
    expect(QuoteResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Quote::class);
});

it('routes quote records by external_id', function () {
    expect(QuoteResource::getRecordRouteKeyName())->toBe('external_id');
});

it('gates the quote resource on the quotes module flag', function () {
    $on = LaravelCrmPlugin::make()->modules(['quotes' => true]);
    $off = LaravelCrmPlugin::make()->modules(['quotes' => false]);

    expect($on->isModuleEnabled('quotes'))->toBeTrue();
    expect($off->isModuleEnabled('quotes'))->toBeFalse();
});
