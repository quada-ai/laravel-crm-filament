<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadSourceLeadsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\ViewLeadSource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'LeadSource RM Tester',
        'email' => 'lsrc-rm-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('declares the relationship as leads', function () {
    $reflection = new ReflectionClass(LeadSourceLeadsRelationManager::class);
    $property = $reflection->getProperty('relationship');

    expect($property->getValue())->toBe('leads');
});

it('extends the Filament RelationManager base class', function () {
    expect(is_subclass_of(
        LeadSourceLeadsRelationManager::class,
        RelationManager::class,
    ))->toBeTrue();
});

it('is read-only', function () {
    $instance = (new ReflectionClass(LeadSourceLeadsRelationManager::class))->newInstanceWithoutConstructor();

    expect($instance->isReadOnly())->toBeTrue();
});

it('returns the sales.leads translation from getTitle()', function () {
    $source = file_get_contents(
        (new ReflectionClass(LeadSourceLeadsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.sales.leads')");
});

it('passes empty header / record / toolbar action arrays', function () {
    $source = file_get_contents(
        (new ReflectionClass(LeadSourceLeadsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->headerActions([])')
        ->and($source)->toContain('->recordActions([])')
        ->and($source)->toContain('->toolbarActions([])');
});

it('defines exactly three columns named title, leadStatus.name, and created_at in order', function () {
    $instance = (new ReflectionClass(LeadSourceLeadsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->toBe(['title', 'leadStatus.name', 'created_at']);
});

it('renders title column as sortable + searchable TextColumn', function () {
    $instance = (new ReflectionClass(LeadSourceLeadsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['title'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

it('renders leadStatus.name column as toggleable TextColumn (status)', function () {
    $instance = (new ReflectionClass(LeadSourceLeadsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['leadStatus.name'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isToggleable())->toBeTrue();
});

it('renders created_at column as sortable TextColumn with since() modifier', function () {
    $source = file_get_contents(
        (new ReflectionClass(LeadSourceLeadsRelationManager::class))->getFileName(),
    );

    $instance = (new ReflectionClass(LeadSourceLeadsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['created_at'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isSortable())->toBeTrue()
        ->and($source)->toContain("TextColumn::make('created_at')")
        ->and($source)->toContain('->since()');
});

it('sets default sort on created_at desc', function () {
    $source = file_get_contents(
        (new ReflectionClass(LeadSourceLeadsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("->defaultSort('created_at', 'desc')");
});

it('declares paginator [10, 25, 50] with default page option 10', function () {
    $source = file_get_contents(
        (new ReflectionClass(LeadSourceLeadsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->paginated([10, 25, 50])')
        ->and($source)->toContain('->defaultPaginationPageOption(10)');
});

it('LeadSource model exposes a leads hasMany relationship', function () {
    $source = new LeadSource;
    $relation = $source->leads();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

// End-to-end: seed a LeadSource + leads bound to it (plus one lead bound to a
// DIFFERENT source as a control) and assert only the owned leads appear on the
// RM's table. Locks the AC's "seeds leads for a source and asserts they appear
// on the RM table" contract AND implicitly locks the RM's relationship-scoping
// via the control lead's absence.
it('renders seeded leads for the source on the RelationManager and excludes leads in other sources', function () {
    $targetSource = LeadSource::create(['name' => 'Referral']);
    $otherSource = LeadSource::create(['name' => 'Direct']);

    // Lead observer stamps external_id + lead_id on creating; no need to supply.
    $leadA = Lead::create([
        'title' => 'Alpha Lead',
        'lead_source_id' => $targetSource->id,
    ]);
    $leadB = Lead::create([
        'title' => 'Beta Lead',
        'lead_source_id' => $targetSource->id,
    ]);

    $otherLead = Lead::create([
        'title' => 'Direct Lead',
        'lead_source_id' => $otherSource->id,
    ]);

    livewire(LeadSourceLeadsRelationManager::class, [
        'ownerRecord' => $targetSource,
        'pageClass' => ViewLeadSource::class,
    ])
        ->assertCanSeeTableRecords([$leadA, $leadB])
        ->assertCanNotSeeTableRecords([$otherLead]);
});
