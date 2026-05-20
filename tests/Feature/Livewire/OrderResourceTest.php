<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\Pages\ListOrders;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Order Tester',
        'email' => 'order-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Order rows on the ListOrders page', function () {
    $order = Order::create([
        'external_id' => (string) Str::uuid(),
        'reference' => 'PO-1234',
    ]);

    livewire(ListOrders::class)
        ->assertCanSeeTableRecords([$order]);
});

it('persists a new Order to the database', function () {
    $record = Order::create([
        'external_id' => (string) Str::uuid(),
    ]);

    expect(Order::query()->whereKey($record->getKey())->exists())->toBeTrue();
});

it('routes Order records by external_id', function () {
    expect(OrderResource::getRecordRouteKeyName())->toBe('external_id');
});
