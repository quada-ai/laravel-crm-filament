<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductPricesRelationManager;

it('declares the relationship as productPrices', function () {
    $reflection = new ReflectionClass(ProductPricesRelationManager::class);
    $property = $reflection->getProperty('relationship');

    expect($property->getValue())->toBe('productPrices');
});

it('extends the Filament RelationManager base class', function () {
    expect(is_subclass_of(
        ProductPricesRelationManager::class,
        RelationManager::class,
    ))->toBeTrue();
});

it('is read-only', function () {
    $instance = (new ReflectionClass(ProductPricesRelationManager::class))->newInstanceWithoutConstructor();

    expect($instance->isReadOnly())->toBeTrue();
});

it('returns the new sections.prices translation from getTitle()', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductPricesRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.sections.prices')");
});

it('passes empty header / record / toolbar action arrays', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductPricesRelationManager::class))->getFileName(),
    );

    expect($source)->toContain('->headerActions([])')
        ->and($source)->toContain('->recordActions([])')
        ->and($source)->toContain('->toolbarActions([])');
});

it('defines exactly two columns named unit_price and currency', function () {
    $instance = (new ReflectionClass(ProductPricesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->toBe(['unit_price', 'currency']);
});

it('renders unit_price with money() formatter and divides cents by 100 in state', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductPricesRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("TextColumn::make('unit_price')")
        ->and($source)->toContain('->money(fn ($record) => $record->currency ?: \'USD\')')
        ->and($source)->toContain('($record->unit_price ?? 0) / 100');
});

it('labels unit_price via money.unit_price and currency via fields.currency', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductPricesRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("__('laravel-crm-filament::labels.money.unit_price')")
        ->and($source)->toContain("__('laravel-crm-filament::labels.fields.currency')");
});

it('sets default sort on the default flag desc so default-flagged price renders first', function () {
    $source = file_get_contents(
        (new ReflectionClass(ProductPricesRelationManager::class))->getFileName(),
    );

    expect($source)->toContain("->defaultSort('default', 'desc')");
});

it('does not surface cost columns or the default boolean as a visible column', function () {
    $instance = (new ReflectionClass(ProductPricesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    $names = array_keys($table->getColumns());

    expect($names)->not->toContain('cost_per_unit')
        ->and($names)->not->toContain('direct_cost')
        ->and($names)->not->toContain('default');
});

it('unit_price state closure returns the stored cents divided by 100', function () {
    $instance = (new ReflectionClass(ProductPricesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['unit_price'];

    // getStateUsing() is the *setter* in Filament v5 (HasCellState); the
    // read-side is the protected $getStateUsing property. Same gotcha pattern
    // locked-in by PersonListColumnsTest (v0.x US-002 of the parity series
    // continuation).
    $ref = new ReflectionProperty(TextColumn::class, 'getStateUsing');
    $ref->setAccessible(true);
    $closure = $ref->getValue($column);

    expect($closure)->toBeInstanceOf(Closure::class);

    $record = new class
    {
        public ?int $unit_price = 4999;
    };

    expect($closure($record))->toBe(49.99);

    $recordNull = new class
    {
        public ?int $unit_price = null;
    };

    expect($closure($recordNull))->toEqual(0);
});

it('flips isMoney() to true via the ->money() chain on unit_price', function () {
    $instance = (new ReflectionClass(ProductPricesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $instance->table(Table::make($instance));

    /** @var TextColumn $column */
    $column = $table->getColumns()['unit_price'];

    expect($column->isMoney())->toBeTrue();
});
