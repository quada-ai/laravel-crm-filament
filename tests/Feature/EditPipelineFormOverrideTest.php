<?php

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\EditPipeline;

it('declares form(Schema) locally on EditPipeline', function (): void {
    $method = new ReflectionMethod(EditPipeline::class, 'form');

    expect($method->getDeclaringClass()->getName())->toBe(EditPipeline::class);
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);
    expect((string) $method->getReturnType())->toBe(Schema::class);
});

it('returns a Schema containing only a name TextInput with required + maxLength(255)', function (): void {
    $page = (new ReflectionClass(EditPipeline::class))->newInstanceWithoutConstructor();
    $schema = Schema::make($page);

    $result = $page->form($schema);

    $components = $result->getComponents(withHidden: true);
    expect($components)->toHaveCount(1);

    $name = $components[0];
    expect($name)->toBeInstanceOf(TextInput::class);
    expect($name->getName())->toBe('name');
    expect($name->isRequired())->toBeTrue();
    expect($name->getMaxLength())->toBe(255);
});

it('does not render an Applies-to Select on the edit page', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Pipelines/Pages/EditPipeline.php'
    );

    expect($source)->not->toContain("Select::make('model')");
    expect($source)->not->toContain('applies_to');
});

it('leaves the header DeleteAction untouched', function (): void {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Pipelines/Pages/EditPipeline.php'
    );

    expect($source)->toContain('Actions\DeleteAction::make()');
});

it('does not modify PipelineResource::form so CreatePipeline still uses it', function (): void {
    $resourceSource = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Pipelines/PipelineResource.php'
    );

    expect($resourceSource)->toContain("Forms\Components\Select::make('model')");
    expect($resourceSource)->toContain('applies_to');
});
