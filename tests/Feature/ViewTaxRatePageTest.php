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
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\Pages\ViewTaxRate;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;

it('extends ViewRecord and binds to TaxRateResource', function (): void {
    expect(is_subclass_of(ViewTaxRate::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewTaxRate::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(TaxRateResource::class);
});

it('getTitle() returns "Tax rate: {name}" using the money.tax_rate label', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = new TaxRate(['name' => 'Standard']);

    $expected = __('laravel-crm-filament::labels.money.tax_rate') . ': Standard';
    expect($page->getTitle())->toBe($expected);
});

it('getHeaderActions() returns exactly [backToIndex, Edit with pencil, Delete with trash] as three pills with button()->hiddenLabel()', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewTaxRate::class, 'getHeaderActions');
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
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());
    expect(substr_count($src, '->button()'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($src, '->hiddenLabel()'))->toBeGreaterThanOrEqual(2);
});

it('content() root component is a Grid with default=1, lg=2 columns and a left Section + right Livewire embed (no Section wrapper on the right)', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = new TaxRate(['name' => 'Standard', 'rate' => 10]);

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
    // AC: right column embeds Livewire::make(...) as a direct Grid child (no Section wrapper).
    expect($childList[1])->toBeInstanceOf(Livewire::class);
    expect($childList[1])->not->toBeInstanceOf(Section::class);
    expect($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('left Section renders TextEntries for name + rate + tax_type + default + description (with description columnSpanFull)', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = new TaxRate([
        'name' => 'Standard',
        'rate' => 10,
        'tax_type' => 'GST',
        'default' => true,
        'description' => 'Standard tax rate',
    ]);

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

    expect($secChildList)->toHaveCount(5);
    $names = array_map(fn ($e) => $e->getName(), $secChildList);
    expect($names)->toBe(['name', 'rate', 'tax_type', 'default', 'description']);

    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }

    // Verify description entry has columnSpanFull applied (source-grep since
    // the setter mutates protected state without a public getter).
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());
    expect($src)->toContain('->columnSpanFull()');
});

it('rate TextEntry renders as "{rate}%" via eager state formatting', function (): void {
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = new TaxRate(['name' => 'Standard', 'rate' => 10]);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($childList[0]);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    // rate is index 1 in the AC-named order.
    $rateEntry = $secChildList[1];
    expect($rateEntry->getName())->toBe('rate');
    expect($rateEntry->getState())->toBe('10%');
});

it('default TextEntry renders as a badge showing "Default" when default=true and null when default=false', function (): void {
    // default=true case: badge shows the translation label
    $pageOn = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $pageOn->record = new TaxRate(['name' => 'Standard', 'rate' => 10, 'default' => true]);

    $schema = Schema::make($pageOn);
    $result = $pageOn->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($childList[0]);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    // default is index 3 in the AC-named order.
    $defaultEntry = $secChildList[3];
    expect($defaultEntry->getName())->toBe('default');
    expect($defaultEntry->getState())->toBe(__('laravel-crm-filament::labels.money.default_tax_rate'));

    // Source contains ->badge() modifier.
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());
    expect($src)->toContain('->badge()');

    // default=false case: state returns null so Filament shows placeholder / nothing.
    $pageOff = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $pageOff->record = new TaxRate(['name' => 'Reduced', 'rate' => 5, 'default' => false]);

    $schemaOff = Schema::make($pageOff);
    $resultOff = $pageOff->content($schemaOff);
    $gridOff = $resultOff->getComponents(withHidden: true)[0];
    $childrenOff = $ref->getValue($gridOff);
    $childListOff = array_values($childrenOff['default'] ?? $childrenOff);
    $secChildrenOff = $secRef->getValue($childListOff[0]);
    $secChildListOff = array_values($secChildrenOff['default'] ?? $secChildrenOff);

    $defaultEntryOff = $secChildListOff[3];
    expect($defaultEntryOff->getState())->toBeNull();
});

it('right column embeds TaxRateProductsRelationManager via Livewire::make with the correct key', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());

    // Structural source-grep: the Livewire embed, ownerData shape, and key.
    expect($src)->toContain('Livewire::make(TaxRateProductsRelationManager::class');
    expect($src)->toContain("'ownerRecord' => \$record");
    expect($src)->toContain("'pageClass' => static::class");
    expect($src)->toContain("->key('tax-rate-products-' . \$record->getKey())");

    // Runtime schema check: right Grid child is a Livewire component.
    $rate = TaxRate::create(['name' => 'Standard', 'rate' => 10]);
    $page = (new ReflectionClass(ViewTaxRate::class))->newInstanceWithoutConstructor();
    $page->record = $rate->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $gridChildren = array_values(($ref->getValue($grid))['default'] ?? $ref->getValue($grid));
    $rightChild = $gridChildren[1];

    expect($rightChild)->toBeInstanceOf(Livewire::class);
});

it('references the AC-named translation keys (sections.details + money.tax_rate + fields.type + money.default_tax_rate)', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());
    expect($src)->toContain('labels.sections.details');
    expect($src)->toContain('labels.money.tax_rate');
    expect($src)->toContain('labels.fields.type');
    expect($src)->toContain('labels.money.default_tax_rate');
});

it('follows the eager $record capture pattern to avoid the ViewPipelineStage-era closure-injection bug', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewTaxRate::class))->getFileName());
    expect($src)->toContain('$record = $this->record;');
});
