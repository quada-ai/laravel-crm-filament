<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Product Tester',
        'email' => 'product-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Product rows on the ListProducts page', function () {
    $product = Product::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Widget Pro',
    ]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$product]);
});

it('persists a new Product to the database', function () {
    Product::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Gadget Lite',
    ]);

    expect(Product::where('name', 'Gadget Lite')->exists())->toBeTrue();
});

it('routes Product records by external_id', function () {
    expect(ProductResource::getRecordRouteKeyName())->toBe('external_id');
});
