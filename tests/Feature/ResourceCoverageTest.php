<?php

use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;

dataset('resources_with_external_id_routes', [
    'Deal' => [DealResource::class, \VentureDrake\LaravelCrm\Models\Deal::class],
    'Person' => [PersonResource::class, \VentureDrake\LaravelCrm\Models\Person::class],
    'Organization' => [OrganizationResource::class, \VentureDrake\LaravelCrm\Models\Organization::class],
    'Task' => [TaskResource::class, \VentureDrake\LaravelCrm\Models\Task::class],
    'Quote' => [QuoteResource::class, \VentureDrake\LaravelCrm\Models\Quote::class],
    'Order' => [OrderResource::class, \VentureDrake\LaravelCrm\Models\Order::class],
    'Invoice' => [InvoiceResource::class, \VentureDrake\LaravelCrm\Models\Invoice::class],
    'PurchaseOrder' => [PurchaseOrderResource::class, \VentureDrake\LaravelCrm\Models\PurchaseOrder::class],
    'Delivery' => [DeliveryResource::class, \VentureDrake\LaravelCrm\Models\Delivery::class],
    'Product' => [ProductResource::class, \VentureDrake\LaravelCrm\Models\Product::class],
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

