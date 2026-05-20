<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ListDeals;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Deal Tester',
        'email' => 'deal-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Deal rows on the ListDeals page', function () {
    $deal = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Enterprise contract',
        'amount' => 100000,
    ]);

    livewire(ListDeals::class)
        ->assertCanSeeTableRecords([$deal]);
});

it('persists a new Deal to the database', function () {
    Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Pilot expansion',
        'amount' => 50000,
    ]);

    expect(Deal::where('title', 'Pilot expansion')->exists())->toBeTrue();
});

it('routes Deal records by external_id', function () {
    expect(DealResource::getRecordRouteKeyName())->toBe('external_id');
});
