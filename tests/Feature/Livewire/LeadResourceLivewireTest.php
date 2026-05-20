<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Lead Tester',
        'email' => 'lead-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Lead rows on the ListLeads page', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Big Co. — Q2 expansion',
    ]);

    livewire(ListLeads::class)
        ->assertCanSeeTableRecords([$lead]);
});

it('persists a new Lead to the database', function () {
    Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Globex annual renewal',
    ]);

    expect(Lead::where('title', 'Globex annual renewal')->exists())->toBeTrue();
});

it('routes Lead records by external_id', function () {
    expect(LeadResource::getRecordRouteKeyName())->toBe('external_id');
});
