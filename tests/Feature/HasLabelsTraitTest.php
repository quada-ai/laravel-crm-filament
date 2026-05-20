<?php

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

dataset('labelTaggedResources', [
    'lead' => [LeadResource::class],
    'deal' => [DealResource::class],
    'quote' => [QuoteResource::class],
    'order' => [OrderResource::class],
    'invoice' => [InvoiceResource::class],
    'delivery' => [DeliveryResource::class],
    'purchase_order' => [PurchaseOrderResource::class],
    'person' => [PersonResource::class],
    'organization' => [OrganizationResource::class],
    'product' => [ProductResource::class],
]);

it('uses the HasLabels trait', function (string $resource) {
    expect(class_uses_recursive($resource))->toContain(HasLabels::class);
})->with('labelTaggedResources');

it('exposes a static labelsField() returning a multi-select labels relation', function (string $resource) {
    $field = $resource::labelsField();

    expect($field)->toBeInstanceOf(Select::class);
    expect($field->getName())->toBe('labels');
    expect($field->isMultiple())->toBeTrue();
    expect($field->getRelationshipName())->toBe('labels');
})->with('labelTaggedResources');

it('preloads + searches labels and exposes a createOptionForm for inline creation', function () {
    $field = LeadResource::labelsField();

    expect($field->isPreloaded())->toBeTrue();
    expect($field->isSearchable())->toBeTrue();
    expect($field->hasCreateOptionActionFormSchema())->toBeTrue();
});

it('mints a new Label row via createOptionUsing and stamps an external_id', function () {
    $field = LeadResource::labelsField();
    $callback = $field->getCreateOptionUsing();

    expect($callback)->not->toBeNull();

    $id = $callback(['name' => 'Hot prospect', 'hex' => '#ff0000', 'description' => 'High priority']);
    $label = Label::find($id);

    expect($label)->not->toBeNull();
    expect($label->name)->toBe('Hot prospect');
    expect($label->hex)->toBe('ff0000'); // setHexAttribute strips leading #
    expect($label->external_id)->not->toBeEmpty();
});

it('persists tags via the labelables polymorphic pivot when attached to a Lead', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Acme Q3 expansion',
    ]);

    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Strategic',
        'hex' => 'ff00ff',
    ]);

    $lead->labels()->attach($label->id);

    $pivot = DB::table('crm_labelables')->where('label_id', $label->id)->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->crm_labelable_type)->toBe(Lead::class);
    expect($pivot->crm_labelable_id)->toBe($lead->id);
});

it('exposes labels() relation on Product via resolveRelationUsing', function () {
    $product = Product::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Widget',
        'currency' => 'USD',
    ]);

    expect(method_exists($product, 'labels') || $product->isRelation('labels'))->toBeTrue();

    // Relations registered through resolveRelationUsing can be invoked via __call.
    $relation = $product->labels();
    expect($relation)->toBeInstanceOf(MorphToMany::class);

    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Featured',
        'hex' => '000000',
    ]);

    $product->labels()->attach($label->id);

    $pivot = DB::table('crm_labelables')->where('label_id', $label->id)->first();
    expect($pivot->crm_labelable_type)->toBe(Product::class);
    expect($pivot->crm_labelable_id)->toBe($product->id);
});

it('exposes labels() relation on Delivery via resolveRelationUsing', function () {
    $delivery = Delivery::create([
        'external_id' => (string) Str::uuid(),
        'order_id' => 1,
    ]);

    $relation = $delivery->labels();
    expect($relation)->toBeInstanceOf(MorphToMany::class);
});

it('wires labelsField into every primary resource form', function (string $resource) {
    $source = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($source)->toContain('static::labelsField()');
})->with('labelTaggedResources');
