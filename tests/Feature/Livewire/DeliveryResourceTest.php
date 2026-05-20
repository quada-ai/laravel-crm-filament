<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\Pages\ListDeliveries;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Delivery Tester',
        'email' => 'delivery-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Delivery rows on the ListDeliveries page', function () {
    $delivery = Delivery::create([
        'external_id' => (string) Str::uuid(),
        'delivery_id' => 'D1001',
    ]);

    livewire(ListDeliveries::class)
        ->assertCanSeeTableRecords([$delivery]);
});

it('persists a new Delivery to the database', function () {
    $record = Delivery::create([
        'external_id' => (string) Str::uuid(),
    ]);

    expect(Delivery::query()->whereKey($record->getKey())->exists())->toBeTrue();
});

it('routes Delivery records by external_id', function () {
    expect(DeliveryResource::getRecordRouteKeyName())->toBe('external_id');
});
