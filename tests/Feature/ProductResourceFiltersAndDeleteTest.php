<?php

use Filament\Actions;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Setting::query()->firstOrCreate(
        ['name' => 'currency'],
        ['external_id' => (string) Str::uuid(), 'value' => 'USD']
    );
    RoleSeeder::seed();
    $this->user = User::create([
        'name' => 'Filter Tester',
        'email' => 'product-filter-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('registers user_owner_id and labels SelectFilters on the table', function () {
    $page = livewire(ListProducts::class)->instance();
    $filters = $page->getTable()->getFilters();

    expect($filters)->toHaveKey('user_owner_id');
    expect($filters)->toHaveKey('labels');
    expect($filters['user_owner_id'])->toBeInstanceOf(SelectFilter::class);
    expect($filters['labels'])->toBeInstanceOf(SelectFilter::class);
});

it('configures user_owner_id filter with multiple+searchable+preload via ownerUser relationship', function () {
    $page = livewire(ListProducts::class)->instance();
    $filter = $page->getTable()->getFilters()['user_owner_id'];

    expect($filter->isMultiple())->toBeTrue();
    expect($filter->getRelationshipName())->toBe('ownerUser');
});

it('configures labels filter with multiple+preload via labels relationship', function () {
    $page = livewire(ListProducts::class)->instance();
    $filter = $page->getTable()->getFilters()['labels'];

    expect($filter->isMultiple())->toBeTrue();
    expect($filter->getRelationshipName())->toBe('labels');
});

it('uses fields.owner and fields.labels translation keys for the filter labels', function () {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Products/ProductResource.php'
    );

    expect($source)->toContain("SelectFilter::make('user_owner_id')");
    expect($source)->toContain("SelectFilter::make('labels')");
    expect($source)->toContain('labels.fields.owner');
    expect($source)->toContain('labels.fields.labels');
});

it('uses created_at desc as the default sort', function () {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Products/ProductResource.php'
    );

    expect($source)->toContain("->defaultSort('created_at', 'desc')");
    expect($source)->not->toContain("->defaultSort('name')");
});

it('registers View, Edit, Delete row actions in that order with Delete requiring confirmation', function () {
    $page = livewire(ListProducts::class)->instance();
    $actions = array_values($page->getTable()->getRecordActions());
    $names = array_map(fn ($a) => $a->getName(), $actions);

    expect($names)->toBe(['view', 'edit', 'delete']);

    $delete = $actions[2];
    expect($delete)->toBeInstanceOf(Actions\DeleteAction::class);
    expect($delete->isConfirmationRequired())->toBeTrue();
});

it('renders ListProducts without errors with the new filters', function () {
    $product = Product::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Filter Render Widget',
    ]);

    livewire(ListProducts::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$product]);
});

it('confirms ProductResource source contains the new Delete action with required modifiers', function () {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Products/ProductResource.php'
    );

    expect($source)->toContain('Actions\\DeleteAction::make()');
    expect($source)->toMatch('/Actions\\\\DeleteAction::make\(\)\s*->button\(\)\s*->hiddenLabel\(\)\s*->requiresConfirmation\(\)/');
});
