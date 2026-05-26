<?php

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Lead Tester',
        'email' => 'lead-table-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<string, TextColumn>
 */
function leadTableColumns(): array
{
    /** @var ListLeads $instance */
    $instance = livewire(ListLeads::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $out = [];
    foreach ($columns as $col) {
        $out[$col->getName()] = $col;
    }

    return $out;
}

it('renders the 10 plan columns in the prescribed order', function () {
    $names = array_keys(leadTableColumns());

    expect($names)->toBe([
        'created_at',
        'lead_id',
        'title',
        'labels.name',
        'amount',
        'person.name',
        'organization.name',
        'pipelineStage.name',
        'leadSource.name',
        'ownerUser.name',
    ]);
});

it('uses created_at relative time as the lead column, visible by default', function () {
    $cols = leadTableColumns();
    $created = $cols['created_at'];

    expect($created->isToggledHiddenByDefault())->toBeFalse();
    expect($created->isToggleable())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain('->since()');
});

it('renders labels.name as a badge with up to 3 entries', function () {
    $cols = leadTableColumns();

    expect($cols['labels.name'])->not->toBeNull();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('labels.name')");
    expect($source)->toContain('->badge()');
    expect($source)->toContain('->limitList(3)');
});

it('preserves sortable on lead_id, title, amount, pipelineStage, created_at', function () {
    $cols = leadTableColumns();

    foreach (['lead_id', 'title', 'amount', 'pipelineStage.name', 'created_at'] as $name) {
        expect($cols[$name]->isSortable())->toBeTrue("column {$name} should be sortable");
    }
});

it('renders person.name, organization.name, leadSource.name and owner as toggleable', function () {
    $cols = leadTableColumns();

    foreach (['person.name', 'organization.name', 'leadSource.name', 'ownerUser.name'] as $name) {
        expect($cols[$name]->isToggleable())->toBeTrue("column {$name} should be toggleable");
    }
});

it('renders ownerUser.name with the Unallocated placeholder', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');

    expect($source)->toContain('labels.misc.unallocated');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('ownerUser.name')");
});

it('returns leads matching person or organization name through global table search', function () {
    $org = Organization::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Acme Industries',
    ]);

    $person = Person::create([
        'external_id' => (string) Str::uuid(),
        'first_name' => 'Jordan',
        'last_name' => 'Stone',
    ]);

    $personLead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Renewal opportunity',
        'person_id' => $person->id,
    ]);

    $orgLead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'New territory pitch',
        'organization_id' => $org->id,
    ]);

    $unrelated = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Wholly unrelated work',
    ]);

    livewire(ListLeads::class)
        ->searchTable('Jordan')
        ->assertCanSeeTableRecords([$personLead])
        ->assertCanNotSeeTableRecords([$orgLead, $unrelated]);

    livewire(ListLeads::class)
        ->searchTable('Acme')
        ->assertCanSeeTableRecords([$orgLead])
        ->assertCanNotSeeTableRecords([$personLead, $unrelated]);
});

it('uses translation keys for all column labels rather than literal strings', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');

    foreach ([
        'labels.fields.number',
        'labels.fields.labels',
        'labels.money.value',
        'labels.fields.contact',
        'labels.fields.organization',
        'labels.sales.stage',
        'labels.sales.lead_source',
        'labels.fields.owner',
        'labels.fields.created',
        'labels.misc.unallocated',
    ] as $key) {
        expect($source)->toContain($key);
    }
});
