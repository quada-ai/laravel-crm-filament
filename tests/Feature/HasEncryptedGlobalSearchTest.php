<?php

use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedGlobalSearch;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

/**
 * Trait covered indirectly via PersonResource — the plugin's canonical
 * consumer of HasEncryptedGlobalSearch. Assertions target the two branches
 * of the override: the parent-delegate path when encryption is off and the
 * PHP-side decrypt-and-match path when encryption is on.
 */
beforeEach(function () {
    RoleSeeder::seed();

    $user = User::create([
        'name' => 'Search User',
        'email' => 'search-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ])->assignRole('Owner');

    $this->actingAs($user);
});

it('composes onto the resource as a trait', function () {
    expect(class_uses_recursive(PersonResource::class))->toContain(HasEncryptedGlobalSearch::class);
});

it('returns an empty collection for a blank search term when encryption is on', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    $results = PersonResource::getGlobalSearchResults('   ');

    expect($results)->toBeInstanceOf(Collection::class);
    expect($results)->toHaveCount(0);
});

it('returns GlobalSearchResult rows for records whose accessor value matches the term', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);
    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
    ]);

    $results = PersonResource::getGlobalSearchResults('lovel');

    expect($results)->toHaveCount(1);
    expect($results->first())->toBeInstanceOf(GlobalSearchResult::class);
    expect($results->first()->title)->toBe('Ada Lovelace');
});

it('returns no results when the term does not match any decrypted accessor value', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Katherine',
        'last_name' => 'Johnson',
    ]);

    $results = PersonResource::getGlobalSearchResults('zzz-no-match');

    expect($results)->toHaveCount(0);
});

it('delegates to the parent implementation when encryption is off', function () {
    config(['laravel-crm.encrypt_db_fields' => false]);

    // Two people so that if the encrypted-branch accidentally runs it would
    // still find a record — the assertion targets return type, not identity.
    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Alan',
        'last_name' => 'Turing',
    ]);

    $results = PersonResource::getGlobalSearchResults('Turing');

    // Encryption-off branch returns whatever the parent gives us — for
    // Filament that's an Illuminate\Support\Collection of GlobalSearchResult.
    expect($results)->toBeInstanceOf(Collection::class);
});
