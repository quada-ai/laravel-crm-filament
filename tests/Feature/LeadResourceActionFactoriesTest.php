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

it('listKanbanToggleActions returns two actions named view_list and view_kanban', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions)->toBeArray()->toHaveCount(2);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[1])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[1]->getName())->toBe('view_kanban');
});

it('listKanbanToggleActions uses the expected heroicons', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getIcon())->toBe('heroicon-o-list-bullet');
    expect($actions[1]->getIcon())->toBe('heroicon-o-view-columns');
});

it('listKanbanToggleActions("list") makes view_list primary and view_kanban gray', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getColor())->toBe('primary');
    expect($actions[1]->getColor())->toBe('gray');
});

it('listKanbanToggleActions("kanban") flips the coloring', function () {
    $actions = LeadResource::listKanbanToggleActions('kanban');

    expect($actions[0]->getColor())->toBe('gray');
    expect($actions[1]->getColor())->toBe('primary');
});

it('listKanbanToggleActions URLs resolve to the index and kanban routes', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions[0]->getUrl())->toBe(LeadResource::getUrl('index'));
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('kanban'));
});
