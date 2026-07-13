<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\RelationManagers\TaxRateProductsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\ViewTaxRate;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'TaxRate RM Tester',
        'email' => 'tax-rm-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('declares the relationship as products', function () {
    $reflection = new ReflectionClass(TaxRateProductsRelationManager::class);
    $property = $reflection->getProperty('relationship');

    expect($property->getValue())->toBe('products');
});

it('extends the Filament RelationManager base class', function () {
    expect(is_subclass_of(
        TaxRateProductsRelationManager::class,
        RelationManager::class,
    ))->toBeTrue();
});

it('is read-only', function () {
    $instance = (new ReflectionClass(TaxRateProductsRelationManager::class))->newInstanceWithoutConstructor();

    expect($instance->isReadOnly())->toBeTrue();
});

it('returns the sales.products translation from getTitle()', function () {
    $source = file_get_contents(
        (new ReflectionClass(TaxRateProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.sales.products')");
});

it('passes empty header / record / toolbar action arrays', function () {
    $source = file_get_contents(
        (new ReflectionClass(TaxRateProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->headerActions([])')
        ->and($source)->toContain('->recordActions([])')
        ->and($source)->toContain('->toolbarActions([])');
});

it('defines exactly three columns named name, code, and active in order', function () {
    $instance = (new ReflectionClass(TaxRateProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->toBe(['name', 'code', 'active']);
});

it('renders name column as sortable + searchable TextColumn', function () {
    $instance = (new ReflectionClass(TaxRateProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['name'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

it('renders code column as toggleable TextColumn (sku)', function () {
    $instance = (new ReflectionClass(TaxRateProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['code'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isToggleable())->toBeTrue();
});

it('renders active column as IconColumn with boolean modifier', function () {
    $source = file_get_contents(
        (new ReflectionClass(TaxRateProductsRelationManager::class))->getFileName(),
    );

    $instance = (new ReflectionClass(TaxRateProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var IconColumn $column */
    $column = $table->getColumns()['active'];

    expect($column)->toBeInstanceOf(IconColumn::class)
        ->and($source)->toContain("IconColumn::make('active')")
        ->and($source)->toContain('->boolean()');
});

it('sets default sort on name asc', function () {
    $source = file_get_contents(
        (new ReflectionClass(TaxRateProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("->defaultSort('name', 'asc')");
});

it('declares paginator [10, 25, 50] with default page option 10', function () {
    $source = file_get_contents(
        (new ReflectionClass(TaxRateProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->paginated([10, 25, 50])')
        ->and($source)->toContain('->defaultPaginationPageOption(10)');
});

it('TaxRate model exposes a products hasMany relationship', function () {
    $rate = new TaxRate;
    $relation = $rate->products();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

// End-to-end: seed a TaxRate + products bound to it (plus one product bound to
// a DIFFERENT rate as a control) and assert only the owned products appear on
// the RM's table. Locks the AC's "seeds products for a tax rate and asserts
// they appear on the RM table" contract AND implicitly locks the RM's
// relationship-scoping via the control product's absence.
it('renders seeded products for the tax rate on the RelationManager and excludes products in other rates', function () {
    $targetRate = TaxRate::create(['name' => 'Standard', 'rate' => 10]);
    $otherRate = TaxRate::create(['name' => 'Reduced', 'rate' => 5]);

    // Product observer stamps external_id on creating; no need to supply it.
    $productA = Product::create([
        'name' => 'Widget Alpha',
        'code' => 'WGT-A',
        'active' => true,
        'tax_rate_id' => $targetRate->id,
    ]);

    $productB = Product::create([
        'name' => 'Widget Beta',
        'code' => 'WGT-B',
        'active' => false,
        'tax_rate_id' => $targetRate->id,
    ]);

    $otherProduct = Product::create([
        'name' => 'Gadget One',
        'code' => 'GDT-1',
        'active' => true,
        'tax_rate_id' => $otherRate->id,
    ]);

    livewire(TaxRateProductsRelationManager::class, [
        'ownerRecord' => $targetRate,
        'pageClass' => ViewTaxRate::class,
    ])
        ->assertCanSeeTableRecords([$productA, $productB])
        ->assertCanNotSeeTableRecords([$otherProduct]);
});
