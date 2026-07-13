<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Resources\LeadStatuses\LeadStatusResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStageProbabilities\PipelineStageProbabilityResource;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

$traitResources = [
    'LeadResource' => LeadResource::class,
    'DealResource' => DealResource::class,
    'QuoteResource' => QuoteResource::class,
    'OrderResource' => OrderResource::class,
    'InvoiceResource' => InvoiceResource::class,
    'PurchaseOrderResource' => PurchaseOrderResource::class,
    'DeliveryResource' => DeliveryResource::class,
    'PersonResource' => PersonResource::class,
    'OrganizationResource' => OrganizationResource::class,
    'ProductResource' => ProductResource::class,
    'CustomerResource' => CustomerResource::class,
    'LeadStatusResource' => LeadStatusResource::class,
    'LeadSourceResource' => LeadSourceResource::class,
    'PipelineStageProbabilityResource' => PipelineStageProbabilityResource::class,
    'ChatWidgetResource' => ChatWidgetResource::class,
    'ChatConversationResource' => ChatConversationResource::class,
    'PipelineResource' => PipelineResource::class,
    'PipelineStageResource' => PipelineStageResource::class,
    'ProductCategoryResource' => ProductCategoryResource::class,
    'LabelResource' => LabelResource::class,
    'FieldResource' => FieldResource::class,
    'FieldGroupResource' => FieldGroupResource::class,
];

$traitDataset = [];
foreach ($traitResources as $name => $fqcn) {
    $traitDataset[$name] = [$fqcn];
}

it('uses the UsesExternalIdRouting trait', function (string $resource) {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive($resource), true))
        ->toBeTrue();
})->with($traitDataset);

it('routes by external_id via the trait', function (string $resource) {
    expect($resource::getRecordRouteKeyName())->toBe('external_id');
})->with($traitDataset);

it('inherits getRecordRouteKeyName from the trait, not from a local override', function (string $resource) {
    $method = new ReflectionMethod($resource, 'getRecordRouteKeyName');
    expect($method->getFileName())
        ->toBe((new ReflectionClass(UsesExternalIdRouting::class))->getFileName());
})->with($traitDataset);

it('inherits getUrl from the trait, not from a local override', function (string $resource) {
    $method = new ReflectionMethod($resource, 'getUrl');
    expect($method->getFileName())
        ->toBe((new ReflectionClass(UsesExternalIdRouting::class))->getFileName());
})->with($traitDataset);

it('round-trips getUrl(view, record => $model) back to the same model via the resource resolver', function (string $resource, string $modelClass, array $attributes) {
    $model = $modelClass::create($attributes + ['external_id' => (string) Str::uuid()]);
    $extId = $model->fresh()->external_id;

    expect($extId)->not->toBeEmpty();

    $url = $resource::getUrl('view', ['record' => $model]);

    expect($url)->toContain($extId);
    expect($url)->not->toEndWith('/' . $model->getKey());

    $resolved = $resource::resolveRecordRouteBinding($extId);

    expect($resolved)->not->toBeNull();
    expect($resolved->getKey())->toBe($model->getKey());
    expect($resolved->external_id)->toBe($extId);
})->with([
    'Lead' => [LeadResource::class, Lead::class, ['title' => 'Round-trip lead']],
    'Deal' => [DealResource::class, Deal::class, ['title' => 'Round-trip deal']],
    'Quote' => [QuoteResource::class, Quote::class, ['title' => 'Round-trip quote']],
    'Order' => [OrderResource::class, Order::class, []],
    'Invoice' => [InvoiceResource::class, Invoice::class, []],
    'PurchaseOrder' => [PurchaseOrderResource::class, PurchaseOrder::class, []],
    'Delivery' => [DeliveryResource::class, Delivery::class, []],
]);

it('leaves a scalar record param alone', function () {
    $url = LeadResource::getUrl('view', ['record' => 'pre-resolved-uuid']);
    expect($url)->toContain('pre-resolved-uuid');
});
