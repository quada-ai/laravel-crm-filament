<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Org Tester',
        'email' => 'org-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Organization rows on the ListOrganizations page', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Acme Corp',
    ]);

    livewire(ListOrganizations::class)
        ->assertCanSeeTableRecords([$org]);
});

it('persists a new Organization to the database', function () {
    Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Zenith Industries',
    ]);

    expect(Organization::where('name', 'Zenith Industries')->exists())->toBeTrue();
});

it('routes Organization records by external_id', function () {
    expect(OrganizationResource::getRecordRouteKeyName())->toBe('external_id');
});
