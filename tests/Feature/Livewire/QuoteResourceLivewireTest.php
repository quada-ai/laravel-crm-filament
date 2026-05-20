<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ListQuotes;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Quote Tester',
        'email' => 'quote-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Quote rows on the ListQuotes page', function () {
    $quote = Quote::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Q-2025-001',
    ]);

    livewire(ListQuotes::class)
        ->assertCanSeeTableRecords([$quote]);
});

it('persists a new Quote to the database', function () {
    Quote::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Q-2025-002',
    ]);

    expect(Quote::where('title', 'Q-2025-002')->exists())->toBeTrue();
});

it('routes Quote records by external_id', function () {
    expect(QuoteResource::getRecordRouteKeyName())->toBe('external_id');
});
