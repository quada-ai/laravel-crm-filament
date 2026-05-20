<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Resources\Customers\Pages\ListCustomers;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Customer Tester',
        'email' => 'customer-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Customer rows on the ListCustomers page', function () {
    $customer = Customer::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Acme Customer',
    ]);

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer]);
});

it('persists a new Customer to the database', function () {
    Customer::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Globex Customer',
    ]);

    expect(Customer::where('name', 'Globex Customer')->exists())->toBeTrue();
});

it('routes Customer records by external_id', function () {
    expect(CustomerResource::getRecordRouteKeyName())->toBe('external_id');
});
