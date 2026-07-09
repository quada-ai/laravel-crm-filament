<?php

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmSideBySideRelationManagers;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;

it('applies HasCrmSideBySideRelationManagers trait to ViewLead', function () {
    // The 2-col Grid + custom tabs strip layout lives in a shared trait since
    // wrapping Filament's stock getRelationManagersContentComponent() inside a
    // Grid columnSpan broke the Livewire tab-switching wire. The trait rolls
    // its own tab strip via wire:click handlers that work correctly nested in
    // a Grid.
    expect(in_array(
        HasCrmSideBySideRelationManagers::class,
        class_uses_recursive(ViewLead::class)
    ))->toBeTrue();
});

it('content() root is a Grid with a Group (left) and a View (right)', function () {
    $page = (new ReflectionClass(ViewLead::class))->newInstanceWithoutConstructor();
    $page->record = new Lead;
    $schema = Schema::make($page);
    $page->content($schema);
    $components = $schema->getComponents(withHidden: true);

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
    expect($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);

    $ref = new ReflectionProperty($components[0], 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($components[0]);
    $children = $children['default'] ?? $children;

    expect($children)->toHaveCount(2);
    expect($children[0])->toBeInstanceOf(Group::class);
    expect($children[1])->toBeInstanceOf(View::class);
    expect($children[0]->getColumnSpan())->toBe(['lg' => 1]);
    expect($children[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('ViewLead exposes setActiveTab(int) and activeTab property', function () {
    $method = new ReflectionMethod(ViewLead::class, 'setActiveTab');
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfRequiredParameters())->toBe(1);

    $prop = new ReflectionProperty(ViewLead::class, 'activeTab');
    expect($prop->getType()?->getName())->toBe('int');
});
