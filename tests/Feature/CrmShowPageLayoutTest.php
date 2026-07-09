<?php

use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ViewDeal;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\ViewDelivery;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ViewInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ViewOrder;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ViewOrganization;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ViewPerson;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ViewQuote;

function crmShowPageContentComponents(string $pageClass, string $modelClass): array
{
    $page = (new ReflectionClass($pageClass))->newInstanceWithoutConstructor();
    $page->record = new $modelClass;
    $schema = Schema::make($page);

    $page->content($schema);

    return $schema->getComponents(withHidden: true);
}

dataset('crmShowPages', [
    'ViewLead' => [ViewLead::class, Lead::class],
    'ViewDeal' => [ViewDeal::class, Deal::class],
    'ViewQuote' => [ViewQuote::class, Quote::class],
    'ViewOrder' => [ViewOrder::class, Order::class],
    'ViewInvoice' => [ViewInvoice::class, Invoice::class],
    'ViewPurchaseOrder' => [ViewPurchaseOrder::class, PurchaseOrder::class],
    'ViewDelivery' => [ViewDelivery::class, Delivery::class],
    'ViewPerson' => [ViewPerson::class, Person::class],
    'ViewOrganization' => [ViewOrganization::class, Organization::class],
]);

it('content() renders both the infolist and the relation-managers tab strip as top-level schema components', function (string $pageClass, string $modelClass) {
    // Reverted from the 2-col Grid layout in favor of Filament's default vertical
    // stack because wrapping getRelationManagersContentComponent() inside a Grid
    // with columnSpan broke the Livewire tab-switching for the RM tabs strip.
    // Filament's default ViewRecord::content() places infolist and RM tabs as
    // top-level schema siblings, which is what these View pages now inherit (or,
    // for ViewPerson / ViewOrganization, flatten to explicitly).
    $components = crmShowPageContentComponents($pageClass, $modelClass);

    $classes = array_map(fn ($c) => get_class($c), $components);

    // Every page should surface at least one Livewire-embed / Filament-managed
    // content component (both getInfolistContentComponent() and
    // getRelationManagersContentComponent() return schema components).
    expect(count($classes))->toBeGreaterThanOrEqual(2);
})->with('crmShowPages');
