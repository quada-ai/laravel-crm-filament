<?php

use Filament\Actions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\Pages\EditPipelineStage;

it('declares form(Schema) locally on EditPipelineStage', function (): void {
    $method = new ReflectionMethod(EditPipelineStage::class, 'form');

    expect($method->getDeclaringClass()->getName())->toBe(EditPipelineStage::class);
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);
    expect((string) $method->getReturnType())->toBe(Schema::class);
});

it('returns a Schema containing the four retained fields (name + Grid[order,color] + probability + description) without pipeline_id', function (): void {
    $page = (new ReflectionClass(EditPipelineStage::class))->newInstanceWithoutConstructor();
    $schema = Schema::make($page);

    $result = $page->form($schema);

    $components = $result->getComponents(withHidden: true);
    expect($components)->toHaveCount(4);

    // 1. name TextInput (required + maxLength 255)
    expect($components[0])->toBeInstanceOf(TextInput::class);
    expect($components[0]->getName())->toBe('name');
    expect($components[0]->isRequired())->toBeTrue();
    expect($components[0]->getMaxLength())->toBe(255);

    // 2. Grid(2) with order + color
    expect($components[1])->toBeInstanceOf(Grid::class);
    $gridRef = new ReflectionProperty(Grid::class, 'childComponents');
    $gridRef->setAccessible(true);
    $gridChildren = $gridRef->getValue($components[1]);
    $gridList = array_values($gridChildren['default'] ?? $gridChildren);

    expect($gridList)->toHaveCount(2);
    expect($gridList[0])->toBeInstanceOf(TextInput::class);
    expect($gridList[0]->getName())->toBe('order');
    expect($gridList[1])->toBeInstanceOf(ColorPicker::class);
    expect($gridList[1]->getName())->toBe('color');

    // 3. pipeline_stage_probability_id Select (searchable + preload)
    expect($components[2])->toBeInstanceOf(Select::class);
    expect($components[2]->getName())->toBe('pipeline_stage_probability_id');
    expect($components[2]->isSearchable())->toBeTrue();
    expect($components[2]->isPreloaded())->toBeTrue();

    // 4. description Textarea (columnSpanFull)
    expect($components[3])->toBeInstanceOf(Textarea::class);
    expect($components[3]->getName())->toBe('description');
});

it('does not render a pipeline_id Select on the edit page', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/EditPipelineStage.php'
    );

    expect($source)->not->toContain("Select::make('pipeline_id')");
    expect($source)->not->toContain("'pipeline_id'");
});

it('leaves the header DeleteAction untouched (getHeaderActions verbatim)', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/PipelineStages/Pages/EditPipelineStage.php'
    );

    expect($source)->toContain('Actions\DeleteAction::make()');
});

it('does not modify PipelineStageResource::form so CreatePipelineStage still uses it (full form including pipeline_id)', function (): void {
    $resourceSource = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/PipelineStages/PipelineStageResource.php'
    );

    expect($resourceSource)->toContain("Forms\Components\Select::make('pipeline_id')");
    expect($resourceSource)->toContain('sales.pipeline');
});

it('preserves getHeaderActions signature and body verbatim', function (): void {
    $method = new ReflectionMethod(EditPipelineStage::class, 'getHeaderActions');

    expect($method->getDeclaringClass()->getName())->toBe(EditPipelineStage::class);
    expect($method->isProtected())->toBeTrue();

    $actions = $method->invoke(new EditPipelineStage);
    expect($actions)->toHaveCount(1);
    expect($actions[0])->toBeInstanceOf(Actions\DeleteAction::class);
});
