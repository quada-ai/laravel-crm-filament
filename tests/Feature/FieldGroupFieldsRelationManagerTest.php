<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrmFilament\RelationManagers\FieldGroupFieldsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\EditFieldGroup;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'FieldGroup RM Tester',
        'email' => 'fg-rm-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('declares the relationship as fields', function () {
    $reflection = new ReflectionClass(FieldGroupFieldsRelationManager::class);
    $property = $reflection->getProperty('relationship');

    expect($property->getValue())->toBe('fields');
});

it('extends the Filament RelationManager base class', function () {
    expect(is_subclass_of(
        FieldGroupFieldsRelationManager::class,
        RelationManager::class,
    ))->toBeTrue();
});

it('is read-only', function () {
    $instance = (new ReflectionClass(FieldGroupFieldsRelationManager::class))->newInstanceWithoutConstructor();

    expect($instance->isReadOnly())->toBeTrue();
});

it('passes empty header / record / toolbar action arrays', function () {
    $source = file_get_contents(
        (new ReflectionClass(FieldGroupFieldsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->headerActions([])')
        ->and($source)->toContain('->recordActions([])')
        ->and($source)->toContain('->toolbarActions([])');
});

it('returns the sales.fields translation from getTitle()', function () {
    $source = file_get_contents(
        (new ReflectionClass(FieldGroupFieldsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.sales.fields')");
});

it('defines exactly five columns in the declared order type, name, required, default, system', function () {
    $instance = (new ReflectionClass(FieldGroupFieldsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->toBe(['type', 'name', 'required', 'default', 'system']);
});

it('renders name column as sortable + searchable TextColumn', function () {
    $instance = (new ReflectionClass(FieldGroupFieldsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['name'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

it('renders required column as IconColumn with boolean modifier', function () {
    $source = file_get_contents(
        (new ReflectionClass(FieldGroupFieldsRelationManager::class))->getFileName(),
    );

    $instance = (new ReflectionClass(FieldGroupFieldsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var IconColumn $column */
    $column = $table->getColumns()['required'];

    expect($column)->toBeInstanceOf(IconColumn::class)
        ->and($source)->toContain("IconColumn::make('required')")
        ->and($source)->toContain('->boolean()');
});

it('renders system column as IconColumn with boolean modifier', function () {
    $source = file_get_contents(
        (new ReflectionClass(FieldGroupFieldsRelationManager::class))->getFileName(),
    );

    $instance = (new ReflectionClass(FieldGroupFieldsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var IconColumn $column */
    $column = $table->getColumns()['system'];

    expect($column)->toBeInstanceOf(IconColumn::class)
        ->and($source)->toContain("IconColumn::make('system')");
});

it('sets default sort on name asc and declares paginator [10, 25, 50] with default page option 10', function () {
    $source = file_get_contents(
        (new ReflectionClass(FieldGroupFieldsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("->defaultSort('name', 'asc')")
        ->and($source)->toContain('->paginated([10, 25, 50])')
        ->and($source)->toContain('->defaultPaginationPageOption(10)');
});

// End-to-end: seed a FieldGroup owner + fields bound to it (plus one field bound
// to a DIFFERENT group as a control) and mount the RM scoped to the owner. Locks
// the AC's "end-to-end mount scoped to an owner FieldGroup" contract AND the
// RM's relationship-scoping via the control field's absence.
it('mounts scoped to a FieldGroup owner and renders its fields while excluding fields in other groups', function () {
    $targetGroup = FieldGroup::create([
        'name' => 'Contact Details',
        'model' => 'Person',
    ]);

    $otherGroup = FieldGroup::create([
        'name' => 'Sales Info',
        'model' => 'Deal',
    ]);

    // Field observer stamps external_id; no need to supply it.
    $fieldA = Field::create([
        'name' => 'Loyalty Score',
        'key' => 'loyalty_score',
        'type' => 'number',
        'field_group_id' => $targetGroup->id,
    ]);

    $fieldB = Field::create([
        'name' => 'Nickname',
        'key' => 'nickname',
        'type' => 'text',
        'field_group_id' => $targetGroup->id,
    ]);

    $otherField = Field::create([
        'name' => 'Deal Rating',
        'key' => 'deal_rating',
        'type' => 'number',
        'field_group_id' => $otherGroup->id,
    ]);

    livewire(FieldGroupFieldsRelationManager::class, [
        'ownerRecord' => $targetGroup,
        'pageClass' => EditFieldGroup::class,
    ])
        ->assertCanSeeTableRecords([$fieldA, $fieldB])
        ->assertCanNotSeeTableRecords([$otherField]);
});
