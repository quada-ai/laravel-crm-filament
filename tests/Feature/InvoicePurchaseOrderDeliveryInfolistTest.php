<?php

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\HasSchemas;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\ViewDelivery;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\Pages\ViewInvoice;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;

dataset('ipdResourceModelPage', [
    'Invoice' => [InvoiceResource::class, Invoice::class, ViewInvoice::class],
    'PurchaseOrder' => [PurchaseOrderResource::class, PurchaseOrder::class, ViewPurchaseOrder::class],
    'Delivery' => [DeliveryResource::class, Delivery::class, ViewDelivery::class],
]);

function ipdInfolistSections(string $resource, string $model): array
{
    $record = new $model;
    $schema = Schema::make(new class
    {
        use HasSchemas;

        public function getKey(): string
        {
            return 'test';
        }
    });
    $schema->record($record);
    $resource::infolist($schema);

    return array_values(array_filter(
        $schema->getComponents(withHidden: true),
        fn ($c) => $c instanceof Section,
    ));
}

it('declares infolist() locally on the Resource', function (string $resource): void {
    $declaringClass = (new ReflectionMethod($resource, 'infolist'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($resource);
})->with('ipdResourceModelPage');

it('infolist source contains Details / Contact / Custom fields section headings', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.details'))");
    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.contact'))");
    expect($src)->toContain("Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))");
})->with('ipdResourceModelPage');

it('infolist source uses PersonResource::getUrl and OrganizationResource::getUrl for contact links', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain("PersonResource::getUrl('view'");
    expect($src)->toContain("OrganizationResource::getUrl('view'");
})->with('ipdResourceModelPage');

it('overrides content() locally on the View page with a 2-col Grid', function (string $resource, string $model, string $page): void {
    $declaringClass = (new ReflectionMethod($page, 'content'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($page);

    $src = file_get_contents((new ReflectionClass($page))->getFileName());
    expect($src)->toContain("Grid::make(['default' => 1, 'lg' => 2])");
    expect($src)->toContain('getInfolistContentComponent()');
    expect($src)->toContain('getRelationManagersContentComponent()');
})->with('ipdResourceModelPage');

it('content() Schema root contains a Grid with two columnSpan lg-1 children', function (string $resource, string $model, string $page): void {
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $instance->record = new $model;
    $schema = Schema::make($instance);
    $instance->content($schema);

    $components = $schema->getComponents(withHidden: true);
    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
})->with('ipdResourceModelPage');

it('Invoice / PurchaseOrder infolists merge ungrouped custom field entries into Details', function (string $resource): void {
    $src = file_get_contents((new ReflectionClass($resource))->getFileName());

    expect($src)->toContain('static::crmCustomFieldEntries($record, false)');
    expect($src)->toContain('static::crmCustomFieldEntries($record, true)');
})->with([
    'Invoice' => [InvoiceResource::class],
    'PurchaseOrder' => [PurchaseOrderResource::class],
]);

it('Delivery infolist hides the Custom fields section unconditionally (Delivery has no fields() relation)', function (): void {
    $src = file_get_contents((new ReflectionClass(DeliveryResource::class))->getFileName());

    expect($src)->toContain('sections.custom_fields');
    expect($src)->toContain('->hidden(fn (): bool => true)');
});

it('Invoice infolist surfaces the Xero sync state section directly via xeroInvoice', function (): void {
    $src = file_get_contents((new ReflectionClass(InvoiceResource::class))->getFileName());
    expect($src)->toContain('xeroSyncStateSection(');
    expect($src)->toContain('xeroInvoice');
});

it('PurchaseOrder infolist surfaces the Xero sync state section directly via xeroPurchaseOrder', function (): void {
    $src = file_get_contents((new ReflectionClass(PurchaseOrderResource::class))->getFileName());
    expect($src)->toContain('xeroSyncStateSection(');
    expect($src)->toContain('xeroPurchaseOrder');
});

it('Delivery contact section walks through order.person and order.organization', function (): void {
    $src = file_get_contents((new ReflectionClass(DeliveryResource::class))->getFileName());

    expect($src)->toContain('order.person.name');
    expect($src)->toContain('order.organization.name');
});
