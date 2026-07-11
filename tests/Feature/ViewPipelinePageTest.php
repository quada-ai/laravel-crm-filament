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
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\ViewPipeline;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

it('extends ViewRecord and binds to PipelineResource', function (): void {
    expect(is_subclass_of(ViewPipeline::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewPipeline::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(PipelineResource::class);
});

it('getTitle() returns "Pipeline: {name}" using the sales.pipeline label', function (): void {
    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();
    $page->record = new Pipeline(['name' => 'Sales Funnel']);

    $expected = __('laravel-crm-filament::labels.sales.pipeline') . ': Sales Funnel';
    expect($page->getTitle())->toBe($expected);
});

it('getHeaderActions() returns backToIndex + Edit pill and NO Delete action', function (): void {
    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewPipeline::class, 'getHeaderActions');
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
    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();
    $page->record = new Pipeline(['name' => 'Sales Funnel']);

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

it('left Section renders stages_list via listWithLineBreaks from ordered pipelineStages()', function (): void {
    $pipeline = Pipeline::create([
        'name' => 'Sales Funnel',
        'model' => Lead::class,
    ]);
    PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Prospect', 'order' => 1]);
    PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Won', 'order' => 3]);
    PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Qualified', 'order' => 2]);

    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();
    $page->record = $pipeline;

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

    expect($secChildList[0])->toBeInstanceOf(TextEntry::class);
    expect($secChildList[0]->getName())->toBe('stages_list');
    expect($secChildList[0]->getState())->toBe(['Prospect', 'Qualified', 'Won']);
});

it('left Section stages_list carries the misc.none placeholder', function (): void {
    $pipeline = Pipeline::create(['name' => 'Empty Funnel', 'model' => Lead::class]);

    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();
    $page->record = $pipeline;

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

    expect($secChildList[0]->getPlaceholder())->toBe(__('laravel-crm-filament::labels.misc.none'));

    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Pipelines/Pages/ViewPipeline.php');
    expect($source)->toContain('->listWithLineBreaks()');
    expect($source)->toContain('->hiddenLabel()');
});

it('right Section renders attached_to as class_basename of pipeline->model', function (): void {
    $pipeline = new Pipeline(['name' => 'Sales Funnel', 'model' => Lead::class]);

    $page = (new ReflectionClass(ViewPipeline::class))->newInstanceWithoutConstructor();
    $page->record = $pipeline;

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

    expect($secChildList[0])->toBeInstanceOf(TextEntry::class);
    expect($secChildList[0]->getName())->toBe('attached_to');
    expect($secChildList[0]->getState())->toBe('Lead');
});

it('sections use the AC-named translation keys', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Pipelines/Pages/ViewPipeline.php');
    expect($source)->toContain('labels.sales.stages');
    expect($source)->toContain('labels.sales.attached_to');
});

it('follows the eager $record capture pattern from ViewRole::content()', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Pipelines/Pages/ViewPipeline.php');
    expect($source)->toContain('$record = $this->record;');
});
