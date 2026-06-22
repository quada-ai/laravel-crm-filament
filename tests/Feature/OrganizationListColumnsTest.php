<?php

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Organization Columns Tester',
        'email' => 'organization-columns-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<string, Column>
 */
function organizationTableColumns(): array
{
    /** @var ListOrganizations $instance */
    $instance = livewire(ListOrganizations::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $out = [];
    foreach ($columns as $col) {
        $out[$col->getName()] = $col;
    }

    return $out;
}

it('renders the 10 plan columns in the prescribed order', function () {
    $names = array_keys(organizationTableColumns());

    expect($names)->toBe([
        'name',
        'xero_contact',
        'organizationType.name',
        'labels.name',
        'open_deals_count',
        'lost_deals_count',
        'won_deals_count',
        'next_activity',
        'ownerUser.name',
        'created_at',
    ]);
});

it('renders the xero_contact column as an IconColumn with a hidden header label and boolean state', function () {
    $cols = organizationTableColumns();

    expect($cols['xero_contact'])->toBeInstanceOf(IconColumn::class);
    expect($cols['xero_contact']->getLabel())->toBe('');

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Organizations/OrganizationResource.php');
    expect($source)->toContain("Tables\\Columns\\IconColumn::make('xero_contact')");
    expect($source)->toContain('->boolean()');
    expect($source)->toContain('$record?->xeroContact !== null');
});

it('renders labels.name as a badge with up to 3 entries and a hex color closure', function () {
    $cols = organizationTableColumns();

    expect($cols['labels.name'])->not->toBeNull();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Organizations/OrganizationResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('labels.name')");
    expect($source)->toContain('->badge()');
    expect($source)->toContain('->limitList(3)');
    expect($source)->toContain('->firstWhere(\'name\', $state)');
    expect($source)->toContain('->hex');
});

it('renders created_at with relative-time formatting and is toggleable', function () {
    $cols = organizationTableColumns();
    $created = $cols['created_at'];

    expect($created->isToggleable())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Organizations/OrganizationResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain('->since()');
});

it('renders ownerUser.name with the Unallocated placeholder', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Organizations/OrganizationResource.php');

    expect($source)->toContain("Tables\\Columns\\TextColumn::make('ownerUser.name')");
    expect($source)->toContain('labels.misc.unallocated');
});

it('renders organizationType.name as a toggleable column', function () {
    $cols = organizationTableColumns();

    expect($cols['organizationType.name']->isToggleable())->toBeTrue();
});

it('drops the prior number_of_employees and raw user_owner_id columns', function () {
    $names = array_keys(organizationTableColumns());

    expect($names)->not->toContain('number_of_employees');
    expect($names)->not->toContain('user_owner_id');
});

it('uses translation keys for the new column labels', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Organizations/OrganizationResource.php');

    foreach ([
        'labels.fields.name',
        'labels.fields.type',
        'labels.fields.labels',
        'labels.fields.open_deals',
        'labels.fields.lost_deals',
        'labels.fields.won_deals',
        'labels.fields.next_activity',
        'labels.fields.owner',
        'labels.fields.created',
        'labels.misc.unallocated',
    ] as $key) {
        expect($source)->toContain($key);
    }
});

it('matches show-page relation counts for open/lost/won deals via withCount', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Acme Corp',
    ]);

    // 2 open deals (closed_at = null)
    Deal::create(['external_id' => (string) Str::uuid(), 'organization_id' => $org->id, 'title' => 'Open A']);
    Deal::create(['external_id' => (string) Str::uuid(), 'organization_id' => $org->id, 'title' => 'Open B']);

    // 1 won
    Deal::create([
        'external_id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Won 1',
        'closed_at' => now(),
        'closed_status' => 'won',
    ]);

    // 3 lost
    Deal::create([
        'external_id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Lost 1',
        'closed_at' => now(),
        'closed_status' => 'lost',
    ]);
    Deal::create([
        'external_id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Lost 2',
        'closed_at' => now(),
        'closed_status' => 'lost',
    ]);
    Deal::create([
        'external_id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'title' => 'Lost 3',
        'closed_at' => now(),
        'closed_status' => 'lost',
    ]);

    /** @var ListOrganizations $instance */
    $instance = livewire(ListOrganizations::class)->instance();
    $rows = $instance->getTable()->getQuery()->where('id', $org->id)->get();

    expect($rows)->toHaveCount(1);
    expect((int) $rows[0]->open_deals_count)->toBe(2);
    expect((int) $rows[0]->lost_deals_count)->toBe(3);
    expect((int) $rows[0]->won_deals_count)->toBe(1);

    // Cross-check against the live relation counts the show page would compute.
    expect($org->deals()->whereNull('closed_at')->count())->toBe(2);
    expect($org->deals()->where('closed_status', 'lost')->count())->toBe(3);
    expect($org->deals()->where('closed_status', 'won')->count())->toBe(1);
});

it('computes next_activity from the soonest incomplete future task due_at', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Bigfoot LLC',
    ]);

    // Past task - excluded by where('due_at', '>=', now())
    Task::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Old',
        'taskable_type' => Organization::class,
        'taskable_id' => $org->id,
        'due_at' => Carbon::now()->subDay(),
    ]);

    // Completed task - excluded by whereNull('completed_at')
    Task::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Done',
        'taskable_type' => Organization::class,
        'taskable_id' => $org->id,
        'due_at' => Carbon::now()->addDay(),
        'completed_at' => Carbon::now(),
    ]);

    // The actual next activity - soonest of two future-incomplete tasks
    $expectedDue = Carbon::now()->addHours(6);
    Task::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Next',
        'taskable_type' => Organization::class,
        'taskable_id' => $org->id,
        'due_at' => $expectedDue,
    ]);
    Task::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Later',
        'taskable_type' => Organization::class,
        'taskable_id' => $org->id,
        'due_at' => Carbon::now()->addDays(3),
    ]);

    $cols = organizationTableColumns();

    $ref = new ReflectionProperty(
        TextColumn::class,
        'getStateUsing'
    );
    $ref->setAccessible(true);
    $closure = $ref->getValue($cols['next_activity']);

    expect($closure)->toBeInstanceOf(Closure::class);

    $value = $closure($org);
    expect($value)->not->toBeNull();
    expect((string) $value)->toBe((string) $expectedDue);
});

it('renders the xero_contact icon column true when a linked XeroContact row exists', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Connected Co',
    ]);

    XeroContact::create([
        'external_id' => (string) Str::uuid(),
        'organization_id' => $org->id,
        'contact_id' => 'xero-uuid-123',
        'name' => 'Connected Co',
    ]);

    $cols = organizationTableColumns();
    $iconCol = $cols['xero_contact'];

    $ref = new ReflectionProperty(
        IconColumn::class,
        'getStateUsing'
    );
    $ref->setAccessible(true);
    $closure = $ref->getValue($iconCol);

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure($org->fresh()))->toBeTrue();
});

it('renders the xero_contact icon column false when no linked XeroContact exists', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Unconnected Co',
    ]);

    $cols = organizationTableColumns();
    $iconCol = $cols['xero_contact'];

    $ref = new ReflectionProperty(
        IconColumn::class,
        'getStateUsing'
    );
    $ref->setAccessible(true);
    $closure = $ref->getValue($iconCol);

    expect($closure($org->fresh()))->toBeFalse();
});

it('renders labels.name color via the hex closure when a label has hex', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Color Test Org',
    ]);

    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'VIP',
        'hex' => 'aabbcc',
    ]);

    $org->labels()->attach($label->id);

    $cols = organizationTableColumns();
    $colorFn = $cols['labels.name']->getColor('VIP', $org);

    expect($colorFn)->toBe('#aabbcc');
});

it('falls back to gray for the labels.name color when no hex is set', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Gray Test Org',
    ]);

    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Plain',
    ]);

    $org->labels()->attach($label->id);

    $cols = organizationTableColumns();
    $color = $cols['labels.name']->getColor('Plain', $org);

    expect($color)->toBe('gray');
});
