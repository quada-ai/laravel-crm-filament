<?php

namespace VentureDrake\LaravelCrmFilament\Support;

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

/**
 * Centralised list of parent FQCNs that the standalone activity/file
 * resources surface via their parent-type filters. Mirrors the morph
 * polymorphic types written by HasCrmActivities relationships.
 */
class ParentTypeOptions
{
    /**
     * @return array<class-string,string>
     */
    public static function all(): array
    {
        return [
            Lead::class => 'Lead',
            Deal::class => 'Deal',
            Person::class => 'Person',
            Organization::class => 'Organization',
            Quote::class => 'Quote',
            Order::class => 'Order',
            Invoice::class => 'Invoice',
            PurchaseOrder::class => 'Purchase Order',
            Delivery::class => 'Delivery',
            Customer::class => 'Customer',
        ];
    }

    /**
     * Resolve the Filament resource slug for a parent FQCN so the
     * "Open parent" action can build a stable URL without hard-coding
     * the route name.
     */
    public static function resourceFor(string $parentType): ?string
    {
        $map = [
            Lead::class => LeadResource::class,
            Deal::class => DealResource::class,
            Person::class => PersonResource::class,
            Organization::class => OrganizationResource::class,
            Quote::class => QuoteResource::class,
            Order::class => OrderResource::class,
            Invoice::class => InvoiceResource::class,
            PurchaseOrder::class => PurchaseOrderResource::class,
            Delivery::class => DeliveryResource::class,
        ];

        return $map[$parentType] ?? null;
    }
}
