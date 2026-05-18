<?php

use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;

dataset('externalIdRoutedResources', [
    'Order' => [OrderResource::class],
    'Invoice' => [InvoiceResource::class],
    'PurchaseOrder' => [PurchaseOrderResource::class],
    'Delivery' => [DeliveryResource::class],
    'Product' => [ProductResource::class],
    'EmailCampaign' => [EmailCampaignResource::class],
    'SmsCampaign' => [SmsCampaignResource::class],
]);

it('routes Phase 2/4 resources by external_id', function (string $resource) {
    expect($resource::getRecordRouteKeyName())->toBe('external_id');
})->with('externalIdRoutedResources');

it('uses the matching core CRM model FQCN for each resource', function () {
    expect(OrderResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Order::class);
    expect(InvoiceResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Invoice::class);
    expect(PurchaseOrderResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\PurchaseOrder::class);
    expect(DeliveryResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Delivery::class);
    expect(ProductResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\Product::class);
    expect(EmailCampaignResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\EmailCampaign::class);
    expect(SmsCampaignResource::getModel())->toBe(\VentureDrake\LaravelCrm\Models\SmsCampaign::class);
});
