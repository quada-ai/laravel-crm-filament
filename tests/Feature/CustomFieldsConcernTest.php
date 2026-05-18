<?php

use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;

it('mixes HasCrmCustomFields into every domain resource that has CRM fields', function () {
    foreach ([
        LeadResource::class, DealResource::class, QuoteResource::class,
        PersonResource::class, OrganizationResource::class, TaskResource::class,
        OrderResource::class, InvoiceResource::class,
        PurchaseOrderResource::class, ProductResource::class,
    ] as $resource) {
        expect(class_uses($resource))
            ->toContain(HasCrmCustomFields::class)
            ->and($resource)->toBe($resource);
    }
});

it('saveCrmCustomFields is a no-op when the record lacks a fields() relation', function () {
    // Pass a plain object without the polymorphic fields() method — the trait
    // should bail out instead of erroring on the missing relation.
    $dummy = new class {};
    LeadResource::saveCrmCustomFields(['custom_fields' => [1 => 'x']], $dummy);
    expect(true)->toBeTrue();
});

it('loadCrmCustomFieldsInto returns input data unchanged when record is null', function () {
    $result = LeadResource::loadCrmCustomFieldsInto(['title' => 'Test'], null);
    expect($result)->toBe(['title' => 'Test']);
});
