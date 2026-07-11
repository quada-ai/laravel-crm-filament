<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages\EditPipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages\ListPipelineStages;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages\ViewPipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(PipelineStageResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = PipelineStageResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(PipelineStageResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_pipeline_stages'));
});

it('table has exactly two AC-named columns in name -> pipeline.name order and drops order + color', function (): void {
    $src = file_get_contents((new ReflectionClass(PipelineStageResource::class))->getFileName());

    expect($src)->toContain("TextColumn::make('name')")
        ->and($src)->toContain("TextColumn::make('pipeline.name')")
        ->and($src)->toContain('labels.sales.attached_to');

    $namePos = strpos($src, "TextColumn::make('name')");
    $pipelinePos = strpos($src, "TextColumn::make('pipeline.name')");
    expect($namePos)->not->toBeFalse()
        ->and($pipelinePos)->not->toBeFalse()
        ->and($namePos)->toBeLessThan($pipelinePos);

    // Scope regression guard for order + color to the table() method body only —
    // form() still declares TextInput::make('order') and ColorPicker::make('color').
    $tableStart = strpos($src, 'public static function table(');
    expect($tableStart)->not->toBeFalse();
    $tableEnd = strpos($src, 'public static function backToIndexAction(', $tableStart);
    $tableBody = substr($src, $tableStart, ($tableEnd !== false ? $tableEnd - $tableStart : strlen($src) - $tableStart));

    expect($tableBody)->not->toContain("TextColumn::make('order')")
        ->and($tableBody)->not->toContain("TextColumn::make('color')");
});

it('recordActions are ViewAction then EditAction, both button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(PipelineStageResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->button()->hiddenLabel()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->button()->hiddenLabel()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos);
});

it('table defaultSort is set to name', function (): void {
    $src = file_get_contents((new ReflectionClass(PipelineStageResource::class))->getFileName());

    expect($src)->toContain("->defaultSort('name')");
});

it('ListPipelineStages::getHeaderActions() returns an empty array', function (): void {
    $page = (new ReflectionClass(ListPipelineStages::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ListPipelineStages::class, 'getHeaderActions');
    $method->setAccessible(true);

    expect($method->invoke($page))->toBe([]);
});

it('ViewPipelineStage extends ViewRecord and binds to PipelineStageResource', function (): void {
    expect(is_subclass_of(ViewPipelineStage::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewPipelineStage::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(PipelineStageResource::class);
});

it('ViewPipelineStage::getTitle() returns "Pipeline stage: {name}" using sales.pipeline_stage label', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Qualified']);

    $expected = __('laravel-crm-filament::labels.sales.pipeline_stage') . ': Qualified';
    expect($page->getTitle())->toBe($expected);
});

it('ViewPipelineStage::getHeaderActions() returns [backToIndex, EditAction with pencil icon, DeleteAction with trash icon]', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewPipelineStage::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Actions\Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(Actions\EditAction::class)
        ->and($actions[1]->getIcon())->toBe('heroicon-m-pencil-square')
        ->and($actions[2])->toBeInstanceOf(Actions\DeleteAction::class)
        ->and($actions[2]->getIcon())->toBe('heroicon-m-trash');

    $names = array_map(fn ($a) => $a->getName(), $actions);
    expect($names)->toContain('delete');
});

it('ViewPipelineStage::content() root is a Grid(default=1, lg=2) with exactly two Sections at columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewPipelineStage::class))->newInstanceWithoutConstructor();
    $page->record = new PipelineStage(['name' => 'Qualified']);

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
        ->and($childList[1])->toBeInstanceOf(Section::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('EditPipelineStage::form(Schema) is declared locally, does not contain Select::make(pipeline_id), and preserves the 4 fields', function (): void {
    $method = new ReflectionMethod(EditPipelineStage::class, 'form');
    expect($method->getDeclaringClass()->getName())->toBe(EditPipelineStage::class)
        ->and($method->isPublic())->toBeTrue()
        ->and($method->getNumberOfParameters())->toBe(1)
        ->and((string) $method->getReturnType())->toBe(Schema::class);

    $source = file_get_contents(
        (new ReflectionClass(EditPipelineStage::class))->getFileName()
    );
    expect($source)->not->toContain("Select::make('pipeline_id')")
        ->and($source)->not->toContain("'pipeline_id'");

    $page = (new ReflectionClass(EditPipelineStage::class))->newInstanceWithoutConstructor();
    $schema = Schema::make($page);
    $result = $page->form($schema);
    $components = $result->getComponents(withHidden: true);

    expect($components)->toHaveCount(4);

    expect($components[0])->toBeInstanceOf(TextInput::class)
        ->and($components[0]->getName())->toBe('name');

    expect($components[1])->toBeInstanceOf(Grid::class);
    $gridRef = new ReflectionProperty(Grid::class, 'childComponents');
    $gridRef->setAccessible(true);
    $gridChildren = $gridRef->getValue($components[1]);
    $gridList = array_values($gridChildren['default'] ?? $gridChildren);

    expect($gridList)->toHaveCount(2)
        ->and($gridList[0])->toBeInstanceOf(TextInput::class)
        ->and($gridList[0]->getName())->toBe('order')
        ->and($gridList[1])->toBeInstanceOf(ColorPicker::class)
        ->and($gridList[1]->getName())->toBe('color');

    expect($components[2])->toBeInstanceOf(Select::class)
        ->and($components[2]->getName())->toBe('pipeline_stage_probability_id');

    expect($components[3])->toBeInstanceOf(Textarea::class)
        ->and($components[3]->getName())->toBe('description');
});

it('does not modify PipelineStageResource::form so CreatePipelineStage still exposes pipeline_id', function (): void {
    $resourceSource = file_get_contents(
        (new ReflectionClass(PipelineStageResource::class))->getFileName()
    );

    expect($resourceSource)->toContain("Select::make('pipeline_id')")
        ->and($resourceSource)->toContain('labels.sales.pipeline');
});
