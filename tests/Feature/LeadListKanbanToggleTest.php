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

it('ListLeads::getHeaderActions returns the segmented viewToggle and Create', function () {
    $actions = invokeHeaderActions(ListLeads::class);

    expect($actions)->toHaveCount(2);

    expect($actions[0]->getName())->toBe('viewToggle');
    expect($actions[0]->getView())->toBe('laravel-crm-filament::components.list-kanban-toggle');
    expect($actions[0]->getViewData())->toMatchArray([
        'current' => 'list',
        'listUrl' => LeadResource::getUrl('index'),
        'kanbanUrl' => LeadResource::getUrl('kanban'),
    ]);

    expect($actions[1])->toBeInstanceOf(CreateAction::class);
});

it('LeadKanban::getHeaderActions returns the segmented viewToggle and Create', function () {
    $actions = invokeHeaderActions(LeadKanban::class);

    expect($actions)->toHaveCount(2);

    expect($actions[0]->getName())->toBe('viewToggle');
    expect($actions[0]->getView())->toBe('laravel-crm-filament::components.list-kanban-toggle');
    expect($actions[0]->getViewData())->toMatchArray([
        'current' => 'kanban',
        'listUrl' => LeadResource::getUrl('index'),
        'kanbanUrl' => LeadResource::getUrl('kanban'),
    ]);

    expect($actions[1])->toBeInstanceOf(CreateAction::class);
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('create'));
});

it('viewToggle on ListLeads links to the list URL via viewData', function () {
    $actions = invokeHeaderActions(ListLeads::class);
    expect($actions[0]->getViewData()['kanbanUrl'])->toBe(LeadResource::getUrl('kanban'));
});

it('viewToggle on LeadKanban points listUrl back to the index', function () {
    $actions = invokeHeaderActions(LeadKanban::class);
    expect($actions[0]->getViewData()['listUrl'])->toBe(LeadResource::getUrl('index'));
});

it('Create action on LeadKanban links to the resource create route', function () {
    $actions = invokeHeaderActions(LeadKanban::class);
    $create = $actions[1];

    expect($create)->toBeInstanceOf(CreateAction::class);
    expect($create->getUrl())->toBe(LeadResource::getUrl('create'));
});
