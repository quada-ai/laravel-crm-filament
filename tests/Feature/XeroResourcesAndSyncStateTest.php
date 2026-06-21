<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Schemas\Components\Section as InfolistSection;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
use VentureDrake\LaravelCrm\Models\XeroItem;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Concerns\HasXeroSyncStateInfolist;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ListXeroContacts;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ListXeroInvoices;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ListXeroItems;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ListXeroPurchaseOrders;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ViewXeroContact;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ViewXeroInvoice;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ViewXeroItem;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ViewXeroPurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroContactResource;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroInvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroItemResource;
use VentureDrake\LaravelCrmFilament\Resources\Xero\XeroPurchaseOrderResource;

class HostUsingXeroSyncStateTrait
{
    use HasXeroSyncStateInfolist;
}

dataset('xeroResources', [
    'XeroContact' => [XeroContactResource::class, XeroContact::class, ListXeroContacts::class, ViewXeroContact::class],
    'XeroItem' => [XeroItemResource::class, XeroItem::class, ListXeroItems::class, ViewXeroItem::class],
    'XeroInvoice' => [XeroInvoiceResource::class, XeroInvoice::class, ListXeroInvoices::class, ViewXeroInvoice::class],
    'XeroPurchaseOrder' => [XeroPurchaseOrderResource::class, XeroPurchaseOrder::class, ListXeroPurchaseOrders::class, ViewXeroPurchaseOrder::class],
]);

it('binds each Xero resource to its core mirror model', function (string $resource, string $model): void {
    expect($resource::getModel())->toBe($model);
})->with('xeroResources');

it('registers only index + view pages (read-only) on each Xero resource', function (string $resource, string $model, string $list, string $view): void {
    $pages = $resource::getPages();
    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');
    expect($pages)->not->toHaveKey('create');
    expect($pages)->not->toHaveKey('edit');
    expect($resource::canCreate())->toBeFalse();
})->with('xeroResources');

it('routes each Xero resource by external_id', function (string $resource): void {
    expect($resource::getRecordRouteKeyName())->toBe('external_id');
})->with('xeroResources');

it('uses an Integrations-flavoured navigation group for each Xero resource', function (string $resource): void {
    expect($resource::getNavigationGroup())->toBe('Integrations');
})->with('xeroResources');

it('exposes an infolist with at least one section on each Xero resource', function (string $resource): void {
    $declaringClass = (new ReflectionMethod($resource, 'infolist'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($resource);
})->with('xeroResources');

it('registers all four Xero resources when the Xero module is enabled', function (): void {
    $panel = Panel::make()->id('xero-on');
    LaravelCrmPlugin::make()->withXero(true)->register($panel);

    expect($panel->getResources())->toContain(
        XeroContactResource::class,
        XeroItemResource::class,
        XeroInvoiceResource::class,
        XeroPurchaseOrderResource::class,
    );
});

it('omits all four Xero resources when the Xero module is disabled', function (): void {
    $panel = Panel::make()->id('xero-off');
    LaravelCrmPlugin::make()->withXero(false)->register($panel);

    expect($panel->getResources())
        ->not->toContain(XeroContactResource::class)
        ->not->toContain(XeroItemResource::class)
        ->not->toContain(XeroInvoiceResource::class)
        ->not->toContain(XeroPurchaseOrderResource::class);
});

dataset('viewPagesWithXeroSection', []);

dataset('resourcesWithXeroSection', [
    'OrderResource' => [OrderResource::class],
    'InvoiceResource' => [InvoiceResource::class],
    'PurchaseOrderResource' => [PurchaseOrderResource::class],
]);

it('builds a Section instance', function (): void {
    $section = HostUsingXeroSyncStateTrait::xeroSyncStateSection(fn () => null);
    expect($section)->toBeInstanceOf(InfolistSection::class);
});

it('configures status/last_sync/xero_id/error entries in the section schema', function (): void {
    $traitSource = file_get_contents((new ReflectionClass(HasXeroSyncStateInfolist::class))->getFileName());

    expect($traitSource)->toContain("'xero_sync_state'");
    expect($traitSource)->toContain("TextEntry::make('xero_status')");
    expect($traitSource)->toContain("TextEntry::make('xero_last_sync')");
    expect($traitSource)->toContain("TextEntry::make('xero_id')");
    expect($traitSource)->toContain("TextEntry::make('xero_error')");
});

it('hides the Xero section when the Xero module is disabled', function (): void {
    config()->set('laravel-crm.modules', []);
    $panel = Panel::make()->id('xero-off-section');
    LaravelCrmPlugin::make()->withXero(false)->register($panel);
    Filament::setCurrentPanel($panel);

    $invoice = Invoice::create([
        'invoice_id' => 'INV-X1',
        'title' => 'Test',
        'currency' => 'USD',
    ]);

    expect(HostUsingXeroSyncStateTrait::xeroSectionVisibleFor($invoice))->toBeFalse();
});

it('shows the Xero section when the Xero module is enabled and a record is bound', function (): void {
    $panel = Panel::make()->id('xero-on-section');
    LaravelCrmPlugin::make()->withXero(true)->register($panel);
    Filament::setCurrentPanel($panel);

    $invoice = Invoice::create([
        'invoice_id' => 'INV-X2',
        'title' => 'Test',
        'currency' => 'USD',
    ]);

    expect(HostUsingXeroSyncStateTrait::xeroSectionVisibleFor($invoice))->toBeTrue();
});

it('reads the linked Xero mirror status when one exists', function (): void {
    $invoice = Invoice::create([
        'invoice_id' => 'INV-X3',
        'title' => 'Test',
        'currency' => 'USD',
    ]);

    XeroInvoice::create([
        'invoice_id' => $invoice->id,
        'xero_id' => 'xero-uuid-123',
        'xero_type' => 'ACCREC',
        'status' => 'AUTHORISED',
        'number' => 'INV-X3',
        'total' => 12345,
        'xero_updated_at' => now(),
    ]);

    $invoice->refresh();
    $mirror = $invoice->xeroInvoice;

    expect($mirror)->not->toBeNull();
    expect($mirror->status)->toBe('AUTHORISED');
    expect($mirror->xero_id)->toBe('xero-uuid-123');
});

it('walks Order to the linked invoice mirror for the Xero state resolver', function (): void {
    $src = file_get_contents((new ReflectionClass(OrderResource::class))->getFileName());
    expect($src)->toContain('invoices()');
    expect($src)->toContain('xeroInvoice');
});

it('uses the direct xeroInvoice relation on InvoiceResource', function (): void {
    $src = file_get_contents((new ReflectionClass(InvoiceResource::class))->getFileName());
    expect($src)->toContain('xeroInvoice');
});

it('uses the direct xeroPurchaseOrder relation on PurchaseOrderResource', function (): void {
    $src = file_get_contents((new ReflectionClass(PurchaseOrderResource::class))->getFileName());
    expect($src)->toContain('xeroPurchaseOrder');
});

it('uses the HasXeroSyncStateInfolist trait on each Xero-aware Resource', function (string $resource): void {
    expect(in_array(HasXeroSyncStateInfolist::class, class_uses_recursive($resource), true))->toBeTrue();
})->with('resourcesWithXeroSection');

it('declares infolist() on each Xero-aware Resource with a Xero sync state section', function (string $resource): void {
    $declaringClass = (new ReflectionMethod($resource, 'infolist'))->getDeclaringClass()->getName();
    expect($declaringClass)->toBe($resource);

    $src = file_get_contents((new ReflectionClass($resource))->getFileName());
    expect($src)->toContain('xeroSyncStateSection(');
})->with('resourcesWithXeroSection');
