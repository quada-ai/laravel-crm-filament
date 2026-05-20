<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ListPeople;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Person Tester',
        'email' => 'person-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('shows existing Person rows on the ListPeople page', function () {
    $person = Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    livewire(ListPeople::class)
        ->assertCanSeeTableRecords([$person]);
});

it('persists a new Person to the database', function () {
    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
    ]);

    expect(Person::query()->where('first_name', 'Grace')->where('last_name', 'Hopper')->exists())->toBeTrue();
});

it('routes Person records by external_id', function () {
    expect(PersonResource::getRecordRouteKeyName())->toBe('external_id');
});
