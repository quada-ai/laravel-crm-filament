<?php

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ListUsers;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'User Columns Tester',
        'email' => 'user-columns-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<string, TextColumn|IconColumn>
 */
function userTableColumns(): array
{
    /** @var ListUsers $instance */
    $instance = livewire(ListUsers::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $out = [];
    foreach ($columns as $col) {
        $out[$col->getName()] = $col;
    }

    return $out;
}

it('renders the 7 plan columns in the prescribed order', function () {
    $names = array_keys(userTableColumns());

    expect($names)->toBe([
        'name',
        'email',
        'email_verified_at',
        'crm_access',
        'roles.name',
        'created_at',
        'last_online_at',
    ]);
});

it('renders email_verified_at as a date column that is toggleable', function () {
    $cols = userTableColumns();
    $verified = $cols['email_verified_at'];

    expect($verified)->toBeInstanceOf(TextColumn::class);
    expect($verified->isToggleable())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('email_verified_at')");
    expect($source)->toContain('->date()');
    expect($source)->toContain('->toggleable()');
});

it('renders crm_access as a boolean IconColumn', function () {
    $cols = userTableColumns();
    $access = $cols['crm_access'];

    expect($access)->toBeInstanceOf(IconColumn::class);

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');
    expect($source)->toContain("Tables\\Columns\\IconColumn::make('crm_access')");
    expect($source)->toContain('->boolean()');
});

it('renders roles.name via a state closure resolving to the first role name', function () {
    $cols = userTableColumns();
    $roles = $cols['roles.name'];

    expect($roles)->toBeInstanceOf(TextColumn::class);

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('roles.name')");
    expect($source)->toContain('->state(fn ($record) => $record->roles->first()?->name)');

    // Runtime: the state closure returns the first role's name for the seeded Admin.
    $ref = new ReflectionProperty(TextColumn::class, 'getStateUsing');
    $ref->setAccessible(true);
    $closure = $ref->getValue($roles);

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure($this->user))->toBe('Admin');
});

it('renders roles.name as an empty cell for users with no role', function () {
    $bareUser = User::create([
        'name' => 'No Role',
        'email' => 'noroleuser' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    $cols = userTableColumns();
    $roles = $cols['roles.name'];

    $ref = new ReflectionProperty(TextColumn::class, 'getStateUsing');
    $ref->setAccessible(true);
    $closure = $ref->getValue($roles);

    expect($closure($bareUser))->toBeNull();
});

it('renders created_at with relative-time formatting via ->since()', function () {
    $cols = userTableColumns();
    $created = $cols['created_at'];

    expect($created)->toBeInstanceOf(TextColumn::class);

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain('->since()');
});

it('renders last_online_at with ->since() and a Never placeholder', function () {
    $cols = userTableColumns();
    $online = $cols['last_online_at'];

    expect($online)->toBeInstanceOf(TextColumn::class);

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('last_online_at')");
    expect($source)->toContain('->placeholder(\'Never\')');

    // Two ->since() call sites: one for created_at, one for last_online_at.
    expect(substr_count($source, '->since()'))->toBe(2);
});

it('drops the prior column inventory entries not in the plan', function () {
    $names = array_keys(userTableColumns());

    // The prior shape had a `roles.name` badge with `->color('gray')` -
    // the new plan keeps `roles.name` but as a plain TextColumn.
    // It also re-orders email_verified_at AFTER email and BEFORE crm_access.
    // No columns outside the 7 plan entries should remain.
    expect($names)->toHaveCount(7);
});

it('uses the US-001 translation keys for crm_access, role, and last_online', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Users/UserResource.php');

    foreach ([
        'labels.fields.crm_access',
        'labels.fields.role',
        'labels.fields.last_online',
    ] as $key) {
        expect($source)->toContain($key);
    }
});

it('preserves UserResource page registration and getModel hook', function () {
    // Locked-in regression: this story only rewrites table(), not the
    // surrounding Resource scaffolding.
    expect(UserResource::getModel())->toBe(User::class);
    expect(array_keys(UserResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('end-to-end: the listing renders without error for a freshly seeded user', function () {
    livewire(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->user]);
});

it('end-to-end: last_online_at column shows the stamped value when present', function () {
    $stamped = Carbon::now()->subHours(2);

    $online = User::create([
        'name' => 'Recently Online',
        'email' => 'recently-online' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
        'last_online_at' => $stamped,
    ]);

    livewire(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$online]);

    // Raw column round-trip
    expect($online->fresh()->last_online_at)->not->toBeNull();
});
