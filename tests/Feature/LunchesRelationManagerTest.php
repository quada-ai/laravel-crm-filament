<?php

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use VentureDrake\LaravelCrmFilament\RelationManagers\LunchesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('declares lunches as the bound relationship', function () {
    $ref = new ReflectionClass(LunchesRelationManager::class);
    $prop = $ref->getProperty('relationship');
    $prop->setAccessible(true);

    expect($prop->getValue())->toBe('lunches');
});

it('extends Filament RelationManager', function () {
    expect(is_subclass_of(LunchesRelationManager::class, RelationManager::class))->toBeTrue();
});

it('is read-only', function () {
    $rm = (new ReflectionClass(LunchesRelationManager::class))->newInstanceWithoutConstructor();

    expect($rm->isReadOnly())->toBeTrue();
});

it('exposes empty header / record / toolbar action arrays', function () {
    $rm = (new ReflectionClass(LunchesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $rm->table(Table::make($rm));

    expect($table->getHeaderActions())->toBe([]);
    expect($table->getRecordActions())->toBe([]);
    expect($table->getToolbarActions())->toBe([]);
});

it('mirrors MeetingsRelationManager title/date/user columns', function () {
    $rm = (new ReflectionClass(LunchesRelationManager::class))->newInstanceWithoutConstructor();
    $table = $rm->table(Table::make($rm));

    $names = array_values(array_map(fn ($c) => $c->getName(), $table->getColumns()));

    expect($names)->toBe(['name', 'start_at', 'finish_at', 'ownerUser.name']);
});

it('is not yet registered on LeadResource (US-008 wires it)', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->not->toContain(LunchesRelationManager::class);
});
