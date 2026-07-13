<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\CreateTaxRate;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\EditTaxRate;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\ListTaxRates;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\ViewTaxRate;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;

/*
 | TaxRateResource is deliberately DIFFERENT from the sibling Settings-cluster
 | parity resources (LabelResource / LeadSourceResource / PipelineStageResource
 | / ProductCategoryResource): the production migration for crm_tax_rates has
 | NO external_id column, so Filament must route by the integer primary key.
 | This test locks that deviation as an explicit contract — the trait MUST NOT
 | be applied and TaxRateResource MUST NOT appear in UsesExternalIdRoutingTraitTest's
 | dataset.
 */

it('does NOT use the UsesExternalIdRouting trait (integer routing preserved)', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(TaxRateResource::class), true))
        ->toBeFalse();
});

it('leaves getRecordRouteKeyName as Filament default (null → integer PK routing)', function (): void {
    // Filament v5's Resource::getRecordRouteKeyName() returns null by default,
    // which makes routing fall back to the model's primary key (integer id).
    // TaxRateResource must NOT override this to 'external_id'.
    expect(TaxRateResource::getRecordRouteKeyName())->toBeNull();
});

it('does not declare a local getRecordRouteKeyName override on TaxRateResource', function (): void {
    $ref = new ReflectionClass(TaxRateResource::class);

    // If a local override existed, ReflectionMethod::getDeclaringClass() would
    // return TaxRateResource::class. Since we inherit from Resource and the
    // parent's default is what we want, the method IS declared on the parent,
    // NOT on TaxRateResource.
    $method = new ReflectionMethod(TaxRateResource::class, 'getRecordRouteKeyName');
    expect($method->getDeclaringClass()->getName())->not->toBe(TaxRateResource::class);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(TaxRateResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = TaxRateResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(TaxRateResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_tax_rates'));
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(TaxRateResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->button()->hiddenLabel()');
    $deletePos = strpos($src, 'Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($deletePos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos)
        ->and($editPos)->toBeLessThan($deletePos);
});

it('preserves the form, table columns, defaultSort, and toolbarActions unchanged', function (): void {
    $src = file_get_contents((new ReflectionClass(TaxRateResource::class))->getFileName());

    // Form components (all 5) preserved.
    expect($src)->toContain("Forms\\Components\\TextInput::make('name')")
        ->and($src)->toContain("Forms\\Components\\TextInput::make('rate')")
        ->and($src)->toContain("Forms\\Components\\TextInput::make('tax_type')")
        ->and($src)->toContain("Forms\\Components\\Toggle::make('default')")
        ->and($src)->toContain("Forms\\Components\\Textarea::make('description')");

    // Table columns (all 5) preserved.
    expect($src)->toContain("Tables\\Columns\\TextColumn::make('name')")
        ->and($src)->toContain("Tables\\Columns\\TextColumn::make('rate')")
        ->and($src)->toContain("Tables\\Columns\\TextColumn::make('tax_type')")
        ->and($src)->toContain("Tables\\Columns\\IconColumn::make('default')")
        ->and($src)->toContain("Tables\\Columns\\TextColumn::make('products_count')")
        ->and($src)->toContain("->counts('products')");

    // Preserved sort + bulk actions.
    expect($src)->toContain("->defaultSort('created_at', 'desc')")
        ->and($src)->toContain('Actions\\BulkActionGroup::make(')
        ->and($src)->toContain('Actions\\DeleteBulkAction::make()');
});

it('getPages() exposes index / create / view / edit (view added by US-008)', function (): void {
    $pages = TaxRateResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(TaxRateResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListTaxRates::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateTaxRate::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewTaxRate::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditTaxRate::class) . "::route('/{record}/edit')");
});

it('resource source references the AC-named actions.back_to_tax_rates translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(TaxRateResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_tax_rates');
});

it('en/fr/es label files declare actions.back_to_tax_rates with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_tax_rates'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_tax_rates'])->not->toBe('');
    }
});

it('ViewTaxRate extends ViewRecord and binds to TaxRateResource', function (): void {
    expect(is_subclass_of(ViewTaxRate::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewTaxRate::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(TaxRateResource::class);
});

it('ViewTaxRate::content() root is a Grid(default=1, lg=2) with a Section on the left and a Livewire embed on the right (no Section wrapper)', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = new TaxRate(['name' => 'Standard', 'rate' => 10]);

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
        // AC: right column is a direct Livewire embed, NOT wrapped in a Section
        // (avoids the double-panel bug documented across ViewLeadSource + the
        // ProductCategory follow-up polish).
        ->and($childList[1])->toBeInstanceOf(Livewire::class)
        ->and($childList[1])->not->toBeInstanceOf(Section::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('ViewTaxRate::getHeaderActions() returns three pills [backToIndex, Edit pencil, Delete trash] with correct icons', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewTaxRate::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Actions\Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(Actions\EditAction::class)
        ->and($actions[1]->getIcon())->toBe('heroicon-m-pencil-square')
        ->and($actions[2])->toBeInstanceOf(Actions\DeleteAction::class)
        ->and($actions[2]->getIcon())->toBe('heroicon-m-trash');
});

it('TaxRateResource is NOT registered in the UsesExternalIdRoutingTraitTest dataset', function (): void {
    // Locks the AC contract: the trait test dataset must not include TaxRateResource,
    // since TaxRateResource intentionally uses integer routing.
    $traitTestPath = dirname(__DIR__) . '/Feature/UsesExternalIdRoutingTraitTest.php';
    $src = file_get_contents($traitTestPath);

    expect($src)->not->toContain('TaxRateResource::class')
        ->and($src)->not->toContain("'TaxRateResource'");
});
