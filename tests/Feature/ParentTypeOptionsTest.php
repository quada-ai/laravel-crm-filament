<?php

use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Support\ParentTypeOptions;

/**
 * Coverage for the Support\ParentTypeOptions map that backs standalone
 * activity/file resource parent-type filters and the "Open parent" action.
 */
it('exposes the full parent-type option map with human labels', function () {
    $map = ParentTypeOptions::all();

    expect($map)->toHaveKey(Lead::class);
    expect($map)->toHaveKey(Deal::class);
    expect($map)->toHaveKey(Person::class);
    expect($map)->toHaveKey(Organization::class);
    expect($map)->toHaveKey(Quote::class);
    expect($map)->toHaveKey(Order::class);
    expect($map)->toHaveKey(Invoice::class);
    expect($map)->toHaveKey(PurchaseOrder::class);
    expect($map)->toHaveKey(Delivery::class);
    expect($map)->toHaveKey(Customer::class);
    expect($map)->toHaveCount(10);

    expect($map[PurchaseOrder::class])->toBe('Purchase Order');
    expect($map[Delivery::class])->toBe('Delivery');
});

dataset('parentResourceMap', [
    'Lead' => [Lead::class, LeadResource::class],
    'Deal' => [Deal::class, DealResource::class],
    'Person' => [Person::class, PersonResource::class],
    'Organization' => [Organization::class, OrganizationResource::class],
    'Quote' => [Quote::class, QuoteResource::class],
    'Order' => [Order::class, OrderResource::class],
    'Invoice' => [Invoice::class, InvoiceResource::class],
    'PurchaseOrder' => [PurchaseOrder::class, PurchaseOrderResource::class],
    'Delivery' => [Delivery::class, DeliveryResource::class],
]);

it('maps each supported parent FQCN to its Filament resource', function (string $parent, string $resource) {
    expect(ParentTypeOptions::resourceFor($parent))->toBe($resource);
})->with('parentResourceMap');

it('returns null for an unmapped parent type (Customer has no dedicated open-parent target here)', function () {
    // Customer is in all() so it can appear in filter dropdowns but has no
    // resource entry in resourceFor() — the standalone "Open parent" action
    // hides itself for these rows.
    expect(ParentTypeOptions::resourceFor(Customer::class))->toBeNull();
});

it('returns null for a completely unknown FQCN', function () {
    expect(ParentTypeOptions::resourceFor('App\\Models\\NotARealModel'))->toBeNull();
});
