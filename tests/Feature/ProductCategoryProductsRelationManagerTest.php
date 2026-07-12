<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductCategoryProductsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ViewProductCategory;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'ProductCategory RM Tester',
        'email' => 'pcat-rm-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('declares the relationship as products', function () {
    $reflection = new ReflectionClass(ProductCategoryProductsRelationManager::class);
    $property = $reflection->getProperty('relationship');

    expect($property->getValue())->toBe('products');
});

it('extends the Filament RelationManager base class', function () {
    expect(is_subclass_of(
        ProductCategoryProductsRelationManager::class,
        RelationManager::class,
    ))->toBeTrue();
});

it('is read-only', function () {
    $instance = (new ReflectionClass(ProductCategoryProductsRelationManager::class))->newInstanceWithoutConstructor();

    expect($instance->isReadOnly())->toBeTrue();
});

it('returns the sales.products translation from getTitle()', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductCategoryProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.sales.products')");
});

it('passes empty header / record / toolbar action arrays', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductCategoryProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->headerActions([])')
        ->and($source)->toContain('->recordActions([])')
        ->and($source)->toContain('->toolbarActions([])');
});

it('defines exactly three columns named name, code, and active in order', function () {
    $instance = (new ReflectionClass(ProductCategoryProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->toBe(['name', 'code', 'active']);
});

it('renders name column as sortable + searchable TextColumn', function () {
    $instance = (new ReflectionClass(ProductCategoryProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['name'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

it('renders code column as toggleable TextColumn (sku)', function () {
    $instance = (new ReflectionClass(ProductCategoryProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['code'];

    expect($column)->toBeInstanceOf(TextColumn::class)
        ->and($column->isToggleable())->toBeTrue();
});

it('renders active column as IconColumn with boolean modifier', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductCategoryProductsRelationManager::class))->getFileName(),
    );

    $instance = (new ReflectionClass(ProductCategoryProductsRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var IconColumn $column */
    $column = $table->getColumns()['active'];

    expect($column)->toBeInstanceOf(IconColumn::class)
        ->and($source)->toContain("IconColumn::make('active')")
        ->and($source)->toContain('->boolean()');
});

it('sets default sort on name asc', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductCategoryProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("->defaultSort('name', 'asc')");
});

it('declares paginator [10, 25, 50] with default page option 10', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductCategoryProductsRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->paginated([10, 25, 50])')
        ->and($source)->toContain('->defaultPaginationPageOption(10)');
});

it('ProductCategory model exposes a products hasMany relationship', function () {
    $category = new ProductCategory;
    $relation = $category->products();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

// End-to-end test per AC: seed a ProductCategory + products for that category (plus
// one product bound to a DIFFERENT category as a control) and assert only the
// owned products appear on the RelationManager's table. Locks the AC's "seeds
// products for a category and asserts they appear on the RM table" contract AND
// implicitly locks the RM's relationship-scoping via the control product's
// absence.
it('renders seeded products for the category on the RelationManager and excludes products in other categories', function () {
    $targetCategory = ProductCategory::create(['name' => 'Widgets']);
    $otherCategory = ProductCategory::create(['name' => 'Gadgets']);

    // Product observer stamps external_id on creating; no need to supply it.
    $productA = Product::create([
        'name' => 'Widget Alpha',
        'code' => 'WGT-A',
        'active' => true,
        'product_category_id' => $targetCategory->id,
    ]);

    $productB = Product::create([
        'name' => 'Widget Beta',
        'code' => 'WGT-B',
        'active' => true,
        'product_category_id' => $targetCategory->id,
    ]);

    $productC = Product::create([
        'name' => 'Widget Gamma',
        'code' => 'WGT-C',
        'active' => false,
        'product_category_id' => $targetCategory->id,
    ]);

    $otherProduct = Product::create([
        'name' => 'Gadget One',
        'code' => 'GDT-1',
        'active' => true,
        'product_category_id' => $otherCategory->id,
    ]);

    livewire(ProductCategoryProductsRelationManager::class, [
        'ownerRecord' => $targetCategory,
        'pageClass' => ViewProductCategory::class,
    ])
        ->assertCanSeeTableRecords([$productA, $productB, $productC])
        ->assertCanNotSeeTableRecords([$otherProduct]);
});
