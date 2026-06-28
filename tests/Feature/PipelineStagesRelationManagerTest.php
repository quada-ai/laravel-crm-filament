<?php

use Filament\Resources\RelationManagers\RelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\PipelineStagesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;

it('binds the PipelineStages relation manager to the pipelineStages relationship', function () {
    $ref = new ReflectionProperty(PipelineStagesRelationManager::class, 'relationship');
    $ref->setAccessible(true);
    expect($ref->getValue())->toBe('pipelineStages');
});

it('extends Filament RelationManager (full-CRUD pattern, not read-only)', function () {
    expect(is_subclass_of(PipelineStagesRelationManager::class, RelationManager::class))->toBeTrue();
});

it('declares form schema with the 5 AC-named fields', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStagesRelationManager::class))->getFileName());

    expect($source)
        ->toContain("TextInput::make('name')")
        ->and($source)->toContain('->required()')
        ->and($source)->toContain("TextInput::make('order')")
        ->and($source)->toContain('->numeric()')
        ->and($source)->toContain('->default(0)')
        ->and($source)->toContain("ColorPicker::make('color')")
        ->and($source)->toContain("Select::make('pipeline_stage_probability_id')")
        ->and($source)->toContain("->relationship('pipelineStageProbability', 'name')")
        ->and($source)->toContain("Textarea::make('description')")
        ->and($source)->toContain('->columnSpanFull()');
});

it('declares table with the 4 AC-named columns including ColorColumn and ->suffix(%)', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStagesRelationManager::class))->getFileName());

    expect($source)
        ->toContain("TextColumn::make('order')")
        ->and($source)->toContain("TextColumn::make('name')")
        ->and($source)->toContain("ColorColumn::make('color')")
        ->and($source)->toContain("TextColumn::make('pipelineStageProbability.percent')")
        ->and($source)->toContain("->suffix('%')");
});

it('declares default sort on order ASC AND ->reorderable(order)', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStagesRelationManager::class))->getFileName());

    expect($source)
        ->toContain("->defaultSort('order', 'asc')")
        ->and($source)->toContain("->reorderable('order')");
});

it('uses the labels namespace for the pipeline_stages section heading', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStagesRelationManager::class))->getFileName());

    expect($source)->toContain("'laravel-crm-filament::labels.sections.pipeline_stages'");
});

it('declares Create + Edit + Delete actions (full CRUD, NOT read-only)', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStagesRelationManager::class))->getFileName());

    expect($source)
        ->toContain('CreateAction::make()')
        ->and($source)->toContain('EditAction::make()')
        ->and($source)->toContain('DeleteAction::make()')
        ->and($source)->not->toContain('isReadOnly(): bool => true');
});

it('PipelineResource::getRelations() registers PipelineStagesRelationManager', function () {
    expect(PipelineResource::getRelations())
        ->toBe([PipelineStagesRelationManager::class]);
});

it('exposes the sections.pipeline_stages translation key in en/fr/es', function () {
    $base = dirname(__DIR__, 2) . '/resources/lang';
    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $base . '/' . $locale . '/labels.php';
        expect($labels['sections']['pipeline_stages'] ?? null)
            ->toBeString()
            ->and($labels['sections']['pipeline_stages'] ?? null)
            ->not->toBe('');
    }
});

it('top-level Pipeline Stages page (PipelineStageResource) remains untouched and still routes', function () {
    expect(class_exists(PipelineStageResource::class))->toBeTrue();
});
