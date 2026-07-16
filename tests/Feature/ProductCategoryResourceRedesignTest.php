<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\CreateProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\EditProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ListProductCategories;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ViewProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(ProductCategoryResource::class), true))
        ->toBeTrue();

    // The trait supplies both getRecordRouteKeyName() and the getUrl() override
    // that swaps the Model parameter for its external_id before delegating to
    // parent::getUrl() — closing the show-page 404 bug documented across the
    // parity series (US-001 continuation) and every trait-adoption story since.
    expect(ProductCategoryResource::getRecordRouteKeyName())->toBe('external_id');
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(ProductCategoryResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = ProductCategoryResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(ProductCategoryResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_product_categories'));
});

it('recordActions register ViewAction then EditAction with button()->hiddenLabel() modifiers in order', function (): void {
    $src = file_get_contents((new ReflectionClass(ProductCategoryResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->button()->hiddenLabel()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->button()->hiddenLabel()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos);
});

it('table defaultSort is set to name', function (): void {
    $src = file_get_contents((new ReflectionClass(ProductCategoryResource::class))->getFileName());

    expect($src)->toContain("->defaultSort('name')");
});

it('getPages() registers the AC-named index / create / view / edit route set with view pointing at ViewProductCategory', function (): void {
    $pages = ProductCategoryResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    // Source-grep on the resource's getPages() body locks the exact registration
    // shape without exercising Filament's internal PageRegistration resolver.
    $src = file_get_contents((new ReflectionClass(ProductCategoryResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListProductCategories::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateProductCategory::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewProductCategory::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditProductCategory::class) . "::route('/{record}/edit')");
});

it('ViewProductCategory extends ViewRecord and binds to ProductCategoryResource', function (): void {
    expect(is_subclass_of(ViewProductCategory::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewProductCategory::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(ProductCategoryResource::class);
});

it('ViewProductCategory::content() root is a Grid(default=1, lg=2) with two Sections at columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewProductCategory::class))->newInstanceWithoutConstructor();
    $page->record = new ProductCategory(['name' => 'Widgets']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $components = $result->getComponents(withHidden: true);

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Grid::class)
        ->and($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($components[0]);
    $childList = array_values($children['default'] ?? $children);

    expect($childList)->toHaveCount(2)
        ->and($childList[0])->toBeInstanceOf(Section::class)
        ->and($childList[0]->getColumnSpan())->toBe(['lg' => 1])
        ->and($childList[1])->toBeInstanceOf(Livewire::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('resource source references the AC-named actions.back_to_product_categories translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(ProductCategoryResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_product_categories');
});

it('en/fr/es label files declare actions.back_to_product_categories and money.product_category with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_product_categories'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_product_categories'])->not->toBe('');

        expect($labels['money']['product_category'] ?? null)->toBeString()
            ->and($labels['money']['product_category'])->not->toBe('');
    }
});

it('inherits getRecordRouteKeyName + getUrl from the UsesExternalIdRouting trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(ProductCategoryResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(ProductCategoryResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});
