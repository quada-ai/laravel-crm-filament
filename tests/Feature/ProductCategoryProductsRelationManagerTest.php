<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductCategoryProductsRelationManager;

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
