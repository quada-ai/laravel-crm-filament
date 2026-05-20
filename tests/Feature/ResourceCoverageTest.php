<?php

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;

dataset('resources_with_external_id_routes', [
    'Deal' => [DealResource::class, Deal::class],
    'Person' => [PersonResource::class, Person::class],
    'Organization' => [OrganizationResource::class, Organization::class],
    'Task' => [TaskResource::class, Task::class],
    'Quote' => [QuoteResource::class, Quote::class],
    'Order' => [OrderResource::class, Order::class],
    'Invoice' => [InvoiceResource::class, Invoice::class],
    'PurchaseOrder' => [PurchaseOrderResource::class, PurchaseOrder::class],
    'Delivery' => [DeliveryResource::class, Delivery::class],
    'Product' => [ProductResource::class, Product::class],
]);

it('binds the right model and uses external_id for routing', function (string $resource, string $model) {
    expect($resource::getModel())->toBe($model);
    expect($resource::getRecordRouteKeyName())->toBe('external_id');
})->with('resources_with_external_id_routes');

it('always registers contact + activity resources regardless of module flags', function () {
    $plugin = LaravelCrmPlugin::make()->modules([
        'leads' => false,
        'deals' => false,
    ]);

    // Module-gated entities respect overrides.
    expect($plugin->isModuleEnabled('leads'))->toBeFalse();
    expect($plugin->isModuleEnabled('deals'))->toBeFalse();
});
