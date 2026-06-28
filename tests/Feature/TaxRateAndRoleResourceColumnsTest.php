<?php

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use VentureDrake\LaravelCrmFilament\Resources\Roles\Pages\ListRoles;
use VentureDrake\LaravelCrmFilament\Resources\Roles\RoleResource;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\ListTaxRates;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'US006 Tester',
        'email' => 'us006-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Owner');
    $this->actingAs($this->user);
});

it('TaxRateResource source declares products_count TextColumn with counts(products) and sales.products label', function () {
    $source = file_get_contents((new ReflectionClass(TaxRateResource::class))->getFileName());

    expect($source)
        ->toContain("TextColumn::make('products_count')")
        ->and($source)->toContain("->counts('products')")
        ->and($source)->toContain('labels.sales.products');
});

it('TaxRateResource table registers products_count as a TextColumn', function () {
    /** @var ListTaxRates $instance */
    $instance = livewire(ListTaxRates::class)->instance();

    $columns = $instance->getTable()->getColumns();

    expect($columns)->toHaveKey('products_count');
    expect($columns['products_count'])->toBeInstanceOf(TextColumn::class);
});

it('RoleResource form source has description TextInput after the name/guard_name Grid', function () {
    $source = file_get_contents((new ReflectionClass(RoleResource::class))->getFileName());

    expect($source)
        ->toContain("TextInput::make('description')")
        ->and($source)->toContain('->maxLength(255)');

    $gridPos = strpos($source, 'Grid::make(2)->schema([');
    $descriptionPos = strpos($source, "TextInput::make('description')");
    $permissionsPos = strpos($source, "CheckboxList::make('permissions')");

    expect($gridPos)->not->toBeFalse();
    expect($descriptionPos)->not->toBeFalse();
    expect($permissionsPos)->not->toBeFalse();
    expect($descriptionPos)->toBeGreaterThan($gridPos);
    expect($descriptionPos)->toBeLessThan($permissionsPos);
});

it('RoleResource table source has description TextColumn with limit(60) after the guard_name column', function () {
    $source = file_get_contents((new ReflectionClass(RoleResource::class))->getFileName());

    expect($source)
        ->toContain("TextColumn::make('description')")
        ->and($source)->toContain('->limit(60)');

    $guardPos = strpos($source, "TextColumn::make('guard_name')");
    $descPos = strpos($source, "TextColumn::make('description')");
    $permsPos = strpos($source, "TextColumn::make('permissions_count')");

    expect($guardPos)->not->toBeFalse();
    expect($descPos)->not->toBeFalse();
    expect($permsPos)->not->toBeFalse();
    expect($descPos)->toBeGreaterThan($guardPos);
    expect($descPos)->toBeLessThan($permsPos);
});

it('RoleResource source registers a crm_role TernaryFilter', function () {
    $source = file_get_contents((new ReflectionClass(RoleResource::class))->getFileName());

    expect($source)->toContain("TernaryFilter::make('crm_role')");
});

it('RoleResource table registers description column and crm_role TernaryFilter', function () {
    /** @var ListRoles $instance */
    $instance = livewire(ListRoles::class)->instance();

    $table = $instance->getTable();

    $columns = $table->getColumns();
    expect($columns)->toHaveKey('description');
    expect($columns['description'])->toBeInstanceOf(TextColumn::class);

    $filters = $table->getFilters();
    expect($filters)->toHaveKey('crm_role');
    expect($filters['crm_role'])->toBeInstanceOf(TernaryFilter::class);
});
