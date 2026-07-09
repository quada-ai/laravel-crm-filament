<?php

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
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

it('content() root is a 2-col Grid (default=1, lg=2) with a Group left + View right', function (string $pageClass, string $modelClass) {
    // Layout: infolist on the left (wrapped in Group so pages like ViewPerson
    // can stack extra Livewire embeds beneath it), and a custom tabs-strip View
    // on the right. See src/Concerns/HasCrmSideBySideRelationManagers.php for
    // why we roll our own tabs strip instead of using Filament's stock
    // getRelationManagersContentComponent() (which breaks tab switching when
    // nested inside a Grid columnSpan).
    $components = crmShowPageContentComponents($pageClass, $modelClass);

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
    expect($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);

    $ref = new ReflectionProperty($components[0], 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($components[0]);
    $children = $children['default'] ?? $children;

    expect($children)->toHaveCount(2);
    expect($children[0])->toBeInstanceOf(Group::class);
    expect($children[1])->toBeInstanceOf(View::class);
    expect($children[0]->getColumnSpan())->toBe(['lg' => 1]);
    expect($children[1]->getColumnSpan())->toBe(['lg' => 1]);
})->with('crmShowPages');
