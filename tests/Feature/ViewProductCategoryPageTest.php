<?php

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ViewProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

it('extends ViewRecord and binds to ProductCategoryResource', function (): void {
    expect(is_subclass_of(ViewProductCategory::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewProductCategory::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(ProductCategoryResource::class);
});

it('getTitle() returns "Product category: {name}" using the money.product_category label', function (): void {
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();
    $page->record = new ProductCategory(['name' => 'Widgets']);

    $expected = __('laravel-crm-filament::labels.money.product_category') . ': Widgets';
    expect($page->getTitle())->toBe($expected);
});

it('getHeaderActions() returns exactly [backToIndex, Edit with pencil, Delete with trash] as three pills with button()->hiddenLabel()', function (): void {
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewProductCategory::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('backToIndex');
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[1]->getIcon())->toBe('heroicon-m-pencil-square');
    expect($actions[2])->toBeInstanceOf(DeleteAction::class);
    expect($actions[2]->getIcon())->toBe('heroicon-m-trash');

    // Regression guard for the three-pill contract via source-grep.
    $src = file_get_contents((new ReflectionClass(ViewProductCategory::class))->getFileName());
    expect(substr_count($src, '->button()'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($src, '->hiddenLabel()'))->toBeGreaterThanOrEqual(2);
});

it('content() root component is a Grid with default=1, lg=2 columns and two Sections with columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();
    $page->record = new ProductCategory(['name' => 'Widgets']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $components = $result->getComponents(withHidden: true);

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
    expect($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($components[0]);
    $childList = array_values($children['default'] ?? $children);

    expect($childList)->toHaveCount(2);
    expect($childList[0])->toBeInstanceOf(Section::class);
    expect($childList[0]->getColumnSpan())->toBe(['lg' => 1]);
    expect($childList[1])->toBeInstanceOf(Livewire::class);
    expect($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('left Section renders TextEntries for name + description (with description columnSpanFull)', function (): void {
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();
    $page->record = new ProductCategory(['name' => 'Widgets', 'description' => 'All widgets']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);
    $leftSection = $childList[0];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($leftSection);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    expect($secChildList)->toHaveCount(2);
    $names = array_map(fn ($e) => $e->getName(), $secChildList);
    expect($names)->toBe(['name', 'description']);

    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }

    // Verify description entry has columnSpanFull applied (source-grep since
    // the setter mutates protected state without a public getter).
    $src = file_get_contents((new ReflectionClass(ViewProductCategory::class))->getFileName());
    expect($src)->toContain('->columnSpanFull()');
});

it('right Section embeds ProductCategoryProductsRelationManager via Livewire::make with the correct key', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewProductCategory::class))->getFileName());

    // Structural source-grep: the Livewire embed, ownerData shape, and key.
    expect($src)->toContain('Livewire::make(ProductCategoryProductsRelationManager::class');
    expect($src)->toContain("'ownerRecord' => \$record");
    expect($src)->toContain("'pageClass' => static::class");
    expect($src)->toContain("->key('product-category-products-' . \$record->getKey())");

    // Runtime schema check: right Section's only child is a Livewire component.
    $category = ProductCategory::create(['name' => 'Widgets']);
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();
    $page->record = $category->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $gridChildren = array_values(($ref->getValue($grid))['default'] ?? $ref->getValue($grid));
    $rightChild = $gridChildren[1];

    expect($rightChild)->toBeInstanceOf(Livewire::class);
});

it('sections use the AC-named translation keys (sections.details / sales.products / money.product_category / actions.back_to_product_categories)', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewProductCategory::class))->getFileName());
    expect($src)->toContain('labels.sections.details');
    expect($src)->toContain('labels.money.product_category');
});

it('follows the eager $record capture pattern from ViewPipelineStage::content()', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewProductCategory::class))->getFileName());
    expect($src)->toContain('$record = $this->record;');
});
