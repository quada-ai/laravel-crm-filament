<?php

use Filament\Facades\Filament;
use Filament\Panel;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrm\Models\ContactType;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Industry;
use VentureDrake\LaravelCrm\Models\OrganizationType;
use VentureDrake\LaravelCrm\Models\ProductAttribute;
use VentureDrake\LaravelCrm\Models\Timezone;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\AddressTypeResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ContactTypes\ContactTypeResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Industries\IndustryResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\OrganizationTypes\OrganizationTypeResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductAttributes\ProductAttributeResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Timezones\TimezoneResource;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductVariationsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Resources\Customers\Pages\CreateCustomer;
use VentureDrake\LaravelCrmFilament\Resources\Customers\Pages\EditCustomer;
use VentureDrake\LaravelCrmFilament\Resources\Customers\Pages\ListCustomers;
use VentureDrake\LaravelCrmFilament\Resources\Customers\Pages\ViewCustomer;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Services\CustomerService;

dataset('lookupResources', [
    'ContactType' => [ContactTypeResource::class, ContactType::class],
    'AddressType' => [AddressTypeResource::class, AddressType::class],
    'OrganizationType' => [OrganizationTypeResource::class, OrganizationType::class],
    'Industry' => [IndustryResource::class, Industry::class],
    'Timezone' => [TimezoneResource::class, Timezone::class],
    'ProductAttribute' => [ProductAttributeResource::class, ProductAttribute::class],
]);

it('binds CustomerResource to the Customer model with external_id routing', function () {
    expect(CustomerResource::getModel())->toBe(Customer::class);
    expect(CustomerResource::getRecordRouteKeyName())->toBe('external_id');
});

it('exposes CRUD pages on CustomerResource', function () {
    $pages = CustomerResource::getPages();
    expect(array_keys($pages))->toEqual(['index', 'create', 'view', 'edit']);
});

it('routes Customer page classes to the CustomerResource', function () {
    foreach ([CreateCustomer::class, EditCustomer::class, ListCustomers::class, ViewCustomer::class] as $page) {
        $reflection = new ReflectionProperty($page, 'resource');
        $reflection->setAccessible(true);
        expect($reflection->getValue())->toBe(CustomerResource::class);
    }
});

it('persists Customer via CustomerService through FormPayload', function () {
    $createPage = (new ReflectionClass(CreateCustomer::class));
    $editPage = (new ReflectionClass(EditCustomer::class));

    // Both page classes should reference CustomerService — the canonical signal that
    // create/update flow through the service rather than the page's default save path.
    $createSource = file_get_contents($createPage->getFileName());
    $editSource = file_get_contents($editPage->getFileName());

    expect($createSource)->toContain('CustomerService');
    expect($createSource)->toContain('FormPayload::wrap');
    expect($editSource)->toContain('CustomerService');
    expect($editSource)->toContain('FormPayload::wrap');
});

it('binds each lookup resource to its core model', function (string $resource, string $model) {
    expect($resource::getModel())->toBe($model);
})->with('lookupResources');

it('places every lookup resource inside the Settings cluster', function (string $resource) {
    expect($resource::getCluster())->toBe(Settings::class);
})->with('lookupResources');

it('exposes list+create+edit pages on each lookup resource', function (string $resource) {
    $pages = $resource::getPages();
    expect(array_keys($pages))->toEqual(['index', 'create', 'edit']);
})->with('lookupResources');

it('registers the Customer module gate and resource on the panel', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['customers' => true]);
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    expect($plugin->isModuleEnabled('customers'))->toBeTrue();
    expect($panel->getResources())->toContain(CustomerResource::class);
});

it('omits CustomerResource from the panel when the customers module is disabled', function () {
    $plugin = LaravelCrmPlugin::make()->modules(['customers' => false]);
    // Force a fresh panel so the TestPanelProvider's customer registration doesn't bleed in.
    $panel = Panel::make()->id('admin-customers-off');
    $plugin->register($panel);

    expect($panel->getResources())->not->toContain(CustomerResource::class);
});

it('registers all six lookup resources on the panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    foreach ([
        ContactTypeResource::class,
        AddressTypeResource::class,
        OrganizationTypeResource::class,
        IndustryResource::class,
        TimezoneResource::class,
        ProductAttributeResource::class,
    ] as $resource) {
        expect($panel->getResources())->toContain($resource);
    }
});

it('wires an industry_id Select onto the Organization form', function () {
    $source = file_get_contents((new ReflectionClass(OrganizationResource::class))->getFileName());
    expect($source)->toContain("Forms\\Components\\Select::make('industry_id')");
    expect($source)->toContain('Industry::query()');
});

it('no longer attaches the ProductVariations relation manager to ProductResource', function () {
    expect(ProductResource::getRelations())->not->toContain(ProductVariationsRelationManager::class);
});

it('wires a product_attribute_id Select onto the ProductVariations form', function () {
    $source = file_get_contents((new ReflectionClass(ProductVariationsRelationManager::class))->getFileName());
    expect($source)->toContain("Select::make('product_attribute_id')");
    expect($source)->toContain('ProductAttribute::query()');
});

it('provides a CustomerService with create+update signature', function () {
    $service = app(CustomerService::class);
    expect(method_exists($service, 'create'))->toBeTrue();
    expect(method_exists($service, 'update'))->toBeTrue();
});
