<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use VentureDrake\LaravelCrmFilament\RelationManagers\ActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('declares timelineActivities as the bound relationship', function () {
    $ref = new ReflectionClass(ActivitiesRelationManager::class);
    $prop = $ref->getProperty('relationship');
    $prop->setAccessible(true);

    expect($prop->getValue())->toBe('timelineActivities');
});

it('extends Filament RelationManager', function () {
    expect(is_subclass_of(ActivitiesRelationManager::class, RelationManager::class))->toBeTrue();
});

it('is read-only', function () {
    $rm = (new ReflectionClass(ActivitiesRelationManager::class))->newInstanceWithoutConstructor();

    expect($rm->isReadOnly())->toBeTrue();
});

it('exposes empty header / record / toolbar action arrays', function () {
    $rm = (new ReflectionClass(ActivitiesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $rm->table(Table::make($rm));

    expect($table->getHeaderActions())->toBe([]);
    expect($table->getRecordActions())->toBe([]);
    expect($table->getToolbarActions())->toBe([]);
});

it('exposes event / user / when columns matching ActivityResource', function () {
    $rm = (new ReflectionClass(ActivitiesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $rm->table(Table::make($rm));

    $names = array_values(array_map(fn ($c) => $c->getName(), $table->getColumns()));

    expect($names)->toBe(['event', 'causeable_id', 'created_at']);
});

it('uses the audit.activity translation key for the tab title', function () {
    $src = file_get_contents((new ReflectionClass(ActivitiesRelationManager::class))->getFileName());

    expect($src)->toContain("'laravel-crm-filament::labels.audit.activity'");
});

it('is not yet registered on LeadResource (US-008 wires it)', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->not->toContain(ActivitiesRelationManager::class);
});
