<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'PO Tester',
        'email' => 'po-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing PurchaseOrder rows on the ListPurchaseOrders page', function () {
    $po = PurchaseOrder::create([
        'external_id' => (string) Str::uuid(),
        'purchase_order_id' => 'PO-1001',
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertCanSeeTableRecords([$po]);
});

it('persists a new PurchaseOrder to the database', function () {
    $record = PurchaseOrder::create([
        'external_id' => (string) Str::uuid(),
    ]);

    expect(PurchaseOrder::query()->whereKey($record->getKey())->exists())->toBeTrue();
});

it('routes PurchaseOrder records by external_id', function () {
    expect(PurchaseOrderResource::getRecordRouteKeyName())->toBe('external_id');
});
