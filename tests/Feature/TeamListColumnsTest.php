<?php

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrmFilament\Resources\Teams\CrmTeamResource;
use VentureDrake\LaravelCrmFilament\Resources\Teams\Pages\ListCrmTeams;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Team Columns Tester',
        'email' => 'team-columns-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<string, Column>
 */
function teamTableColumns(): array
{
    /** @var ListCrmTeams $instance */
    $instance = livewire(ListCrmTeams::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $out = [];
    foreach ($columns as $col) {
        $out[$col->getName()] = $col;
    }

    return $out;
}

it('renders the 5 plan columns in the prescribed order', function () {
    $names = array_keys(teamTableColumns());

    expect($names)->toBe([
        'name',
        'users_count',
        'userCreated.name',
        'ownerUser.name',
        'created_at',
    ]);
});

it('renders the users_count column with the Users header label (not Members)', function () {
    $cols = teamTableColumns();
    $usersCount = $cols['users_count'];

    expect($usersCount)->toBeInstanceOf(TextColumn::class);
    expect($usersCount->getLabel())->toBe('Users');

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Teams/CrmTeamResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('users_count')");
    expect($source)->toContain('labels.fields.users');
    // Regression guard: the prior column used `fields.members`. The new
    // column must NOT use that key.
    expect($source)->not->toContain('labels.fields.members');
});

it('renders ownerUser.name with an Unallocated placeholder', function () {
    $cols = teamTableColumns();
    $owner = $cols['ownerUser.name'];

    expect($owner)->toBeInstanceOf(TextColumn::class);
    expect($owner->getPlaceholder())->toBe('Unallocated');

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Teams/CrmTeamResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('ownerUser.name')");
    expect($source)->toContain('labels.misc.unallocated');
});

it('renders userCreated.name as a toggleable column', function () {
    $cols = teamTableColumns();
    $createdBy = $cols['userCreated.name'];

    expect($createdBy)->toBeInstanceOf(TextColumn::class);
    expect($createdBy->isToggleable())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Teams/CrmTeamResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('userCreated.name')");
});

it('renders created_at with relative-time formatting via ->since() and toggleable', function () {
    $cols = teamTableColumns();
    $created = $cols['created_at'];

    expect($created)->toBeInstanceOf(TextColumn::class);
    expect($created->isToggleable())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Teams/CrmTeamResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain('->since()');
});

it('applies withCount(\'users\') via the resource\'s getEloquentQuery() override', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Teams/CrmTeamResource.php');

    expect($source)->toContain('public static function getEloquentQuery()');
    expect($source)->toContain("withCount('users')");
});

it('exactly 5 columns in the table — no extra entries', function () {
    $names = array_keys(teamTableColumns());

    expect($names)->toHaveCount(5);
});

it('preserves CrmTeamResource model + pages + slug surface', function () {
    expect(CrmTeamResource::getModel())->toBe(Team::class);
    expect(CrmTeamResource::getSlug())->toBe('crm-teams');
    expect(array_keys(CrmTeamResource::getPages()))->toBe(['index', 'create', 'view', 'edit']);
});

it('end-to-end: users_count reflects the team\'s users() relation', function () {
    $owner = User::create([
        'name' => 'Team Owner',
        'email' => 'team-owner' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    $team = Team::create([
        'user_id' => $owner->id,
        'name' => 'Engineering',
    ]);

    // Attach 3 distinct members via the pivot.
    $members = collect();
    for ($i = 0; $i < 3; $i++) {
        $member = User::create([
            'name' => 'Member ' . $i,
            'email' => 'member' . $i . uniqid() . '@example.com',
            'password' => bcrypt('secret'),
        ]);
        $members->push($member);
        DB::table('crm_team_user')->insert([
            'crm_team_id' => $team->id,
            'user_id' => $member->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Refetch via the resource's own query so the withCount aliasing fires.
    $rows = CrmTeamResource::getEloquentQuery()->where('id', $team->id)->get();

    expect($rows->first()->users_count)->toBe(3);
    expect($team->fresh()->users()->count())->toBe(3);
});

it('end-to-end: ownerUser is null when user_owner_id is not set, exercising the placeholder', function () {
    $creator = User::create([
        'name' => 'Team Creator',
        'email' => 'team-creator' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    $team = Team::create([
        'user_id' => $creator->id,
        'name' => 'Sales',
    ]);

    // Polyfilled ownerUser relation returns null when user_owner_id is null.
    expect($team->fresh()->ownerUser)->toBeNull();
});

it('end-to-end: the listing renders without error for a seeded team', function () {
    $creator = User::create([
        'name' => 'List Render Tester',
        'email' => 'list-render' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    $team = Team::create([
        'user_id' => $creator->id,
        'name' => 'Marketing',
    ]);

    livewire(ListCrmTeams::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$team]);
});
