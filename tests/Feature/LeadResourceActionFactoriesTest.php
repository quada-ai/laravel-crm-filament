<?php

use Filament\Actions\Action;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('convertAction returns an Action named convertToDeal with success color and confirmation', function () {
    $action = LeadResource::convertAction();

    expect($action)->toBeInstanceOf(Action::class);
    expect($action->getName())->toBe('convertToDeal');
    expect($action->getColor())->toBe('success');
    expect($action->isConfirmationRequired())->toBeTrue();
});

it('convertAction is hidden when the lead is already converted, visible otherwise', function () {
    $action = LeadResource::convertAction();

    $unconverted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Open lead',
    ]);

    $action->record($unconverted);
    expect($action->isVisible())->toBeTrue();

    $converted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Already done',
        'converted_at' => now()->subDay(),
    ]);

    $action->record($converted);
    expect($action->isVisible())->toBeFalse();
});

it('convertAction visibility returns false when the record is null', function () {
    $action = LeadResource::convertAction();
    $action->record(null);

    expect($action->isVisible())->toBeFalse();
});

it('convertAction execution calls doConvertToDeal and wires up a success notification', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Convert me',
        'amount' => 1500,
        'currency' => 'USD',
    ]);

    $action = LeadResource::convertAction();
    $action->record($lead->fresh());
    $action->call();

    expect($lead->fresh()->converted_at)->not->toBeNull();
    expect(Deal::query()->where('title', 'Convert me')->exists())->toBeTrue();

    $src = file_get_contents(dirname(__DIR__, 2) . '/src/Resources/Leads/LeadResource.php');
    expect($src)->toContain('Notification::make()');
    expect($src)->toContain('->success()');
});

it('listKanbanToggleActions returns a single segmented viewToggle action', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions)->toBeArray()->toHaveCount(1);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('viewToggle');
});

it('viewToggle uses the segmented control Blade view', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getView())->toBe('laravel-crm-filament::components.list-kanban-toggle');
});

it('viewToggle carries current=list when called with "list"', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getViewData())->toMatchArray([
        'current' => 'list',
        'listUrl' => LeadResource::getUrl('index'),
        'kanbanUrl' => LeadResource::getUrl('kanban'),
    ]);
});

it('viewToggle carries current=kanban when called with "kanban"', function () {
    $actions = LeadResource::listKanbanToggleActions('kanban');

    expect($actions[0]->getViewData()['current'])->toBe('kanban');
});

it('viewToggle URLs resolve to the index and kanban routes', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getViewData()['listUrl'])->toBe(LeadResource::getUrl('index'));
    expect($actions[0]->getViewData()['kanbanUrl'])->toBe(LeadResource::getUrl('kanban'));
});
