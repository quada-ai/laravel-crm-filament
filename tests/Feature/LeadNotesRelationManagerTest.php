<?php

use Filament\Forms;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\NotesRelationManager;

it('extends NotesRelationManager', function () {
    expect(is_subclass_of(LeadNotesRelationManager::class, NotesRelationManager::class))->toBeTrue();
});

it('overrides the $view property to point at the lead-notes Blade template', function () {
    $ref = new ReflectionClass(LeadNotesRelationManager::class);
    $prop = $ref->getProperty('view');
    $prop->setAccessible(true);

    expect($prop->getDeclaringClass()->getName())->toBe(LeadNotesRelationManager::class);

    $rm = $ref->newInstanceWithoutConstructor();
    expect($prop->getValue($rm))->toBe('laravel-crm-filament::lead-notes');
});

it('returns a 2-field form schema with Note + Noted-at and no pinned field', function () {
    $rm = (new ReflectionClass(LeadNotesRelationManager::class))->newInstanceWithoutConstructor();
    $schema = $rm->form(Schema::make($rm));

    $components = $schema->getComponents();
    $names = array_values(array_map(fn ($c) => $c->getName(), $components));

    expect($names)->toBe(['content', 'noted_at']);
    expect($components[0])->toBeInstanceOf(Forms\Components\Textarea::class);
    expect($components[1])->toBeInstanceOf(Forms\Components\DateTimePicker::class);

    expect($names)->not->toContain('pinned');
});

it('inherits the parent table configuration (columns and actions)', function () {
    $ref = new ReflectionClass(LeadNotesRelationManager::class);

    expect($ref->hasMethod('table'))->toBeTrue();
    expect($ref->getMethod('table')->getDeclaringClass()->getName())
        ->toBe(NotesRelationManager::class);
});

it('inherits the parent relationship binding', function () {
    $ref = new ReflectionClass(LeadNotesRelationManager::class);
    $prop = $ref->getProperty('relationship');
    $prop->setAccessible(true);

    expect($prop->getValue())->toBe('notes');
});
