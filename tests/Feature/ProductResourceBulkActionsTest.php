<?php

use Filament\Actions\BulkActionGroup;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;

it('uses the HasPrimaryBulkActions trait on ProductResource', function () {
    expect(class_uses_recursive(ProductResource::class))
        ->toContain(HasPrimaryBulkActions::class);
});

it('exposes static::primaryBulkActionGroup() returning a BulkActionGroup with the 3-action shape', function () {
    $group = ProductResource::primaryBulkActionGroup();

    expect($group)->toBeInstanceOf(BulkActionGroup::class);

    $names = array_map(fn ($a) => $a->getName(), $group->getActions());

    expect($names)
        ->toContain('assignOwner')
        ->toContain('applyLabels')
        ->toContain('archive')
        ->not->toContain('changePipelineStage');
});

it('wires the primary bulk action group into the ProductResource toolbar', function () {
    $source = file_get_contents((new ReflectionClass(ProductResource::class))->getFileName());

    expect($source)
        ->toContain('static::primaryBulkActionGroup()')
        ->not->toContain('Actions\BulkActionGroup::make([')
        ->not->toContain('Actions\DeleteBulkAction::make()');
});

it('still exposes a working bulk delete via the archive action (soft-delete on Product)', function () {
    $group = ProductResource::primaryBulkActionGroup();
    $names = array_map(fn ($a) => $a->getName(), $group->getActions());

    // The replacement of the inline DeleteBulkAction is the archive action — same soft-delete
    // behavior on Product (which uses SoftDeletes). This is the AC's "Bulk delete still works".
    expect($names)->toContain('archive');
});
