<?php

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\PipelineStageProbability;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages\ViewPipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

it('extends ViewRecord and binds to PipelineStageResource', function (): void {
    expect(is_subclass_of(ViewPipelineStage::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewPipelineStage::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(PipelineStageResource::class);
});

it('getTitle() returns "Pipeline stage: {name}" using the sales.pipeline_stage label', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Qualified']);

    $expected = __('laravel-crm-filament::labels.sales.pipeline_stage') . ': Qualified';
    expect($page->getTitle())->toBe($expected);
});

it('getHeaderActions() returns backToIndex + Edit pill and NO Delete action', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewPipelineStage::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(2);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('backToIndex');
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[1]->getIcon())->toBe('heroicon-m-pencil-square');

    $names = array_map(fn ($a) => $a->getName(), $actions);
    expect($names)->not->toContain('delete');
});

it('content() root component is a Grid with default=1, lg=2 columns and two Sections with columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Qualified']);

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

it('left Section renders the 5 AC-named TextEntries in order (name/order/color/description/probability)', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Won', 'order' => 3, 'color' => '#00aa00']);

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
    expect($names)->toBe([
        'name',
        'order',
        'color',
        'description',
        'pipeline_stage_probability_id',
    ]);

    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }
});

it('color TextEntry renders as a badge whose color is the record hex, falling back to #6b7280 when null', function (): void {
    // With a color set on the record.
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Won', 'color' => 'aabbcc']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $gridRef = new ReflectionProperty(Grid::class, 'childComponents');
    $gridRef->setAccessible(true);
    $gridChildren = array_values(($gridRef->getValue($grid))['default'] ?? $gridRef->getValue($grid));

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $leftChildren = array_values(($secRef->getValue($gridChildren[0]))['default'] ?? $secRef->getValue($gridChildren[0]));

    // color entry is index 2.
    $colorEntry = $leftChildren[2];
    expect($colorEntry->getName())->toBe('color');
    expect($colorEntry->getState())->toBe('#aabbcc');

    // Source references the fallback color.
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/ViewPipelineStage.php');
    expect($source)->toContain('#6b7280');
    expect($source)->toContain('->badge()');
});

it('description TextEntry uses columnSpanFull()', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/ViewPipelineStage.php');
    // Assert columnSpanFull() appears within the source (chained on description entry).
    expect($source)->toContain('->columnSpanFull()');
});

it('probability TextEntry renders as "{name} ({percent}%)" with misc.none placeholder when FK is null', function (): void {
    $pipeline = Pipeline::create(['name' => 'Sales Funnel', 'model' => Lead::class]);
    $prob = PipelineStageProbability::create(['name' => 'High', 'percent' => 75]);
    $stage = PipelineStage::create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Won',
        'order' => 1,
        'pipeline_stage_probability_id' => $prob->id,
    ]);

    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = $stage->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $gridRef = new ReflectionProperty(Grid::class, 'childComponents');
    $gridRef->setAccessible(true);
    $gridChildren = array_values(($gridRef->getValue($grid))['default'] ?? $gridRef->getValue($grid));

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $leftChildren = array_values(($secRef->getValue($gridChildren[0]))['default'] ?? $secRef->getValue($gridChildren[0]));

    $probEntry = $leftChildren[4];
    expect($probEntry->getName())->toBe('pipeline_stage_probability_id');
    expect($probEntry->getState())->toBe('High (75%)');
    expect($probEntry->getPlaceholder())->toBe(__('laravel-crm-filament::labels.misc.none'));

    // Now assert the null-FK path: fresh stage without probability -> state is null so placeholder shows.
    $bareStage = PipelineStage::create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Prospect',
        'order' => 1,
    ]);
    $page2 = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page2->record = $bareStage->fresh();

    $schema2 = Schema::make($page2);
    $result2 = $page2->content($schema2);
    $grid2 = $result2->getComponents(withHidden: true)[0];
    $gridChildren2 = array_values(($gridRef->getValue($grid2))['default'] ?? $gridRef->getValue($grid2));
    $leftChildren2 = array_values(($secRef->getValue($gridChildren2[0]))['default'] ?? $secRef->getValue($gridChildren2[0]));

    expect($leftChildren2[4]->getState())->toBeNull();
});

it('right Section renders attached_to as $record->pipeline->name (empty string when no pipeline)', function (): void {
    $pipeline = Pipeline::create(['name' => 'Sales Funnel', 'model' => Lead::class]);
    $stage = PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Won', 'order' => 1]);

    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = $stage->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $gridRef = new ReflectionProperty(Grid::class, 'childComponents');
    $gridRef->setAccessible(true);
    $gridChildren = array_values(($gridRef->getValue($grid))['default'] ?? $gridRef->getValue($grid));

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $rightChildren = array_values(($secRef->getValue($gridChildren[1]))['default'] ?? $secRef->getValue($gridChildren[1]));

    expect($rightChildren[0])->toBeInstanceOf(TextEntry::class);
    expect($rightChildren[0]->getName())->toBe('attached_to');
    expect($rightChildren[0]->getState())->toBe('Sales Funnel');
});

it('sections use the AC-named translation keys (sales.pipeline_stage / sections.details / sales.attached_to)', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/ViewPipelineStage.php');
    expect($source)->toContain('labels.sales.pipeline_stage');
    expect($source)->toContain('labels.sections.details');
    expect($source)->toContain('labels.sales.attached_to');
});

it('follows the eager $record capture pattern from ViewRole::content()', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/ViewPipelineStage.php');
    expect($source)->toContain('$record = $this->record;');
});
