<?php

use Filament\Actions\CreateAction;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\LeadKanban;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;

function invokeHeaderActions(string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    return $method->invoke($instance);
}

it('ListLeads::getHeaderActions returns view_list (primary), view_kanban (gray), Create', function () {
    $actions = invokeHeaderActions(ListLeads::class);

    expect($actions)->toHaveCount(3);

    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('primary');
    expect($actions[0]->getUrl())->toBe(LeadResource::getUrl('index'));

    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('gray');
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('kanban'));

    expect($actions[2])->toBeInstanceOf(CreateAction::class);
});

it('LeadKanban::getHeaderActions returns view_list (gray), view_kanban (primary), Create', function () {
    $actions = invokeHeaderActions(LeadKanban::class);

    expect($actions)->toHaveCount(3);

    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('gray');
    expect($actions[0]->getUrl())->toBe(LeadResource::getUrl('index'));

    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('primary');
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('kanban'));

    expect($actions[2])->toBeInstanceOf(CreateAction::class);
    expect($actions[2]->getUrl())->toBe(LeadResource::getUrl('create'));
});

it('Clicking view_kanban from ListLeads navigates to the kanban URL', function () {
    $actions = invokeHeaderActions(ListLeads::class);
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('kanban'));
});

it('Clicking view_list from LeadKanban navigates to the index URL', function () {
    $actions = invokeHeaderActions(LeadKanban::class);
    expect($actions[0]->getUrl())->toBe(LeadResource::getUrl('index'));
});

it('Create action on LeadKanban links to the resource create route', function () {
    $actions = invokeHeaderActions(LeadKanban::class);
    $create = $actions[2];

    expect($create)->toBeInstanceOf(CreateAction::class);
    expect($create->getUrl())->toBe(LeadResource::getUrl('create'));
});
