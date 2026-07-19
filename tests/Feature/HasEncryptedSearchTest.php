<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedSearch;

/**
 * Structural + behavioral assertions for the HasEncryptedSearch::modifyQuery
 * factory. Verifies the returned closure is a no-op when encryption is off
 * or the search term is blank, and filters by PHP-side accessor match when
 * encryption is on and a term is present.
 */
it('returns a closure that leaves the query untouched when encryption is off', function () {
    config(['laravel-crm.encrypt_db_fields' => false]);
    request()->replace(['tableSearch' => 'anything']);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Alpha',
        'last_name' => 'Zebra',
    ]);
    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Beta',
        'last_name' => 'Zebra',
    ]);

    $closure = HasEncryptedSearch::modifyQuery(fn ($r) => $r->first_name);
    $query = Person::query();
    $closure($query);

    // Encryption off ⇒ closure is a no-op ⇒ full record set returned.
    expect($query->count())->toBe(2);
});

it('returns a closure that is a no-op when the search term is blank', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);
    request()->replace(['tableSearch' => '   ']);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Alpha',
        'last_name' => 'Zebra',
    ]);

    $closure = HasEncryptedSearch::modifyQuery(fn ($r) => $r->first_name);
    $query = Person::query();
    $closure($query);

    expect($query->count())->toBe(1);
});

it('filters records via the accessor closure when encryption is on and a term is present', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    $alpha = Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Alpha',
        'last_name' => 'Zebra',
    ]);
    $beta = Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Beta',
        'last_name' => 'Zebra',
    ]);

    request()->replace(['tableSearch' => 'alp']);

    // Accessor rehydrates the record via a fresh query so it can compare
    // against decrypted first/last name columns (the concern's inner
    // ->select('id') otherwise strips them).
    $closure = HasEncryptedSearch::modifyQuery(function ($r) {
        $full = Person::find($r->id);

        return ($full->first_name ?? '') . ' ' . ($full->last_name ?? '');
    });
    $query = Person::query();
    $closure($query);

    // Applying the closure adds a whereIn narrowing to the query bindings.
    $wheres = collect($query->getQuery()->wheres);
    expect($wheres->pluck('type'))->toContain('In');

    $results = $query->pluck('id')->all();

    expect($results)->toContain($alpha->id);
    expect($results)->not->toContain($beta->id);
});

it('applies a whereIn narrowing that excludes all records when accessor never matches', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Whatever',
    ]);

    request()->replace(['tableSearch' => 'noneofthem']);

    $closure = HasEncryptedSearch::modifyQuery(fn ($r) => 'never-matches');
    $query = Person::query();
    $closure($query);

    expect($query->count())->toBe(0);
});

it('falls back to the request(search) key when tableSearch is absent', function () {
    config(['laravel-crm.encrypt_db_fields' => true]);

    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Gamma',
    ]);
    Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Delta',
    ]);

    request()->replace(['search' => 'gam']);

    $closure = HasEncryptedSearch::modifyQuery(function ($r) {
        $full = Person::find($r->id);

        return $full->first_name ?? '';
    });
    $query = Person::query();
    $closure($query);

    $names = $query->get()->map(fn (Person $p) => $p->first_name)->all();
    expect($names)->toBe(['Gamma']);
});
