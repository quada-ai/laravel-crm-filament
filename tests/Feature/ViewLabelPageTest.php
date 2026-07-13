<?php

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ViewLabel;

it('extends ViewRecord and binds to LabelResource', function (): void {
    expect(is_subclass_of(ViewLabel::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewLabel::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(LabelResource::class);
});

it('getTitle() returns "Label: {name}" using the sales.label label', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = new Label(['name' => 'Priority']);

    $expected = __('laravel-crm-filament::labels.sales.label') . ': Priority';
    expect($page->getTitle())->toBe($expected);
});

it('getHeaderActions() returns exactly [backToIndex, Edit with pencil, Delete with trash] as three pills with button()->hiddenLabel()', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewLabel::class, 'getHeaderActions');
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
    $src = file_get_contents((new ReflectionClass(ViewLabel::class))->getFileName());
    expect(substr_count($src, '->button()'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($src, '->hiddenLabel()'))->toBeGreaterThanOrEqual(2);
});

it('content() root component is a Grid with default=1, lg=2 columns and two Section children', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = new Label(['name' => 'Priority', 'hex' => 'ff0000']);

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
    expect($childList[1])->toBeInstanceOf(Section::class);
    expect($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('left Details Section renders name TextEntry + hex ColorEntry + description TextEntry with columnSpanFull', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = new Label(['name' => 'Priority', 'hex' => 'ff0000', 'description' => 'High priority items']);

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

    expect($secChildList)->toHaveCount(3);
    expect($secChildList[0])->toBeInstanceOf(TextEntry::class);
    expect($secChildList[0]->getName())->toBe('name');
    expect($secChildList[1])->toBeInstanceOf(ColorEntry::class);
    expect($secChildList[1]->getName())->toBe('hex');
    expect($secChildList[2])->toBeInstanceOf(TextEntry::class);
    expect($secChildList[2]->getName())->toBe('description');

    // description entry has columnSpanFull applied — verify via source-grep.
    $src = file_get_contents((new ReflectionClass(ViewLabel::class))->getFileName());
    expect($src)->toContain('->columnSpanFull()');
});

it('right Usage Section renders 4 badge TextEntries counting leads / deals / people / organizations', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = new Label(['name' => 'Priority']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);
    $rightSection = $childList[1];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($rightSection);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    expect($secChildList)->toHaveCount(4);
    $names = array_map(fn ($e) => $e->getName(), $secChildList);
    expect($names)->toBe(['leads_count', 'deals_count', 'people_count', 'organizations_count']);

    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }

    // AC-mandated badge shape: all 4 Usage entries call ->badge().
    // Source-grep counts (4 badges in Usage + no badge on left entries).
    $src = file_get_contents((new ReflectionClass(ViewLabel::class))->getFileName());
    expect(substr_count($src, '->badge()'))->toBe(4);
});

it('Usage badge state reads counts from the polymorphic morphedByMany relations', function (): void {
    // Seed a Label + one Lead attached to it via the polymorphic pivot.
    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Priority',
        'hex' => 'ff0000',
    ]);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Widget lead',
    ]);
    $lead->labels()->attach($label->id);

    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = $label->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $gridChildren = array_values(($ref->getValue($grid))['default'] ?? $ref->getValue($grid));
    $rightSection = $gridChildren[1];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = array_values(($secRef->getValue($rightSection))['default'] ?? $secRef->getValue($rightSection));

    // leads_count entry (index 0) should have state == 1.
    // deals_count (1), people_count (2), organizations_count (3) should all be 0.
    // Read via the public getState() API rather than reflection on the
    // internal "constantState" property (the plain-value form of ->state()
    // is stored via $getConstantStateUsing; getState() is the canonical
    // read-side).
    expect($secChildren[0]->getState())->toBe(1);
    expect($secChildren[1]->getState())->toBe(0);
    expect($secChildren[2]->getState())->toBe(0);
    expect($secChildren[3]->getState())->toBe(0);
});

it('references the AC-named translation keys (sections.details, sections.usage, sales.label)', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewLabel::class))->getFileName());
    expect($src)->toContain('labels.sections.details');
    expect($src)->toContain('labels.sections.usage');
    expect($src)->toContain('labels.sales.label');
    expect($src)->toContain('labels.sales.leads');
    expect($src)->toContain('labels.sales.deals');
    expect($src)->toContain('labels.sales.people');
    expect($src)->toContain('labels.sales.organizations');
});

it('follows the eager $record capture pattern to avoid the ViewPipelineStage-era closure-injection bug', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewLabel::class))->getFileName());
    expect($src)->toContain('$record = $this->record;');
});

it('registers view page route on LabelResource::getPages() between create and edit', function (): void {
    $pages = LabelResource::getPages();
    $keys = array_keys($pages);
    expect($keys)->toBe(['index', 'create', 'view', 'edit']);

    // Regression guard: source-grep the view registration line.
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());
    expect($src)->toContain("'view' => ViewLabel::route('/{record}')");
});
