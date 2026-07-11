<?php

declare(strict_types=1);

use Filament\Actions;
use VentureDrake\LaravelCrmFilament\RelationManagers\PipelineStagesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(PipelineResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = PipelineResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(PipelineResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_pipelines'));
});

it('registers ViewAction then EditAction as icon-only pills in the table recordActions', function (): void {
    $src = file_get_contents((new ReflectionClass(PipelineResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->button()->hiddenLabel()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->button()->hiddenLabel()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos);
});

it('preserves defaultSort, toolbarActions, columns, and getRelations shape', function (): void {
    $src = file_get_contents((new ReflectionClass(PipelineResource::class))->getFileName());

    expect($src)->toContain("->defaultSort('name')")
        ->and($src)->toContain('Actions\\BulkActionGroup::make([')
        ->and($src)->toContain('Actions\\DeleteBulkAction::make()')
        ->and($src)->toContain("TextColumn::make('name')")
        ->and($src)->toContain("TextColumn::make('model')")
        ->and($src)->toContain("TextColumn::make('pipelineStages_count')");

    expect(PipelineResource::getRelations())->toBe([
        PipelineStagesRelationManager::class,
    ]);
});
