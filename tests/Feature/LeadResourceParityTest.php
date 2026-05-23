<?php

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\EditLead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\LeadKanban;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Parity Tester',
        'email' => 'lead-parity-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

function parityInvokeHeaderActions(string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    return $method->invoke($instance);
}

it('table registers every new column from the parity plan', function () {
    $columns = livewire(ListLeads::class)->instance()->getTable()->getColumns();
    $names = array_values(array_map(fn ($c) => $c->getName(), $columns));

    expect($names)->toBe([
        'created_at',
        'lead_id',
        'title',
        'labels.name',
        'amount',
        'person.name',
        'organization.name',
        'pipelineStage.name',
        'leadSource.name',
        'ownerUser.name',
    ]);
});

it('table registers every new filter with the expected multi-select config', function () {
    $filters = livewire(ListLeads::class)->instance()->getTable()->getFilters();

    $byName = [];
    foreach ($filters as $filter) {
        $byName[$filter->getName()] = $filter;
    }

    expect(array_keys($byName))->toContain('user_owner_id', 'labels', 'lead_source_id', 'pipeline_stage_id');

    /** @var SelectFilter $owner */
    $owner = $byName['user_owner_id'];
    /** @var SelectFilter $labels */
    $labels = $byName['labels'];
    /** @var SelectFilter $source */
    $source = $byName['lead_source_id'];

    expect($owner->isMultiple())->toBeTrue();
    expect($labels->isMultiple())->toBeTrue();
    expect($source->isMultiple())->toBeTrue();

    expect($owner->getRelationshipName())->toBe('ownerUser');
    expect($labels->getRelationshipName())->toBe('labels');
});

it('convertAction factory contract matches the parity plan', function () {
    $action = LeadResource::convertAction();

    expect($action)->toBeInstanceOf(Action::class);
    expect($action->getName())->toBe('convertToDeal');
    expect($action->isConfirmationRequired())->toBeTrue();
});

it('convertAction visibility closure flips between open and converted leads', function () {
    $action = LeadResource::convertAction();

    $open = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Open',
    ]);
    $action->record($open);
    expect($action->isVisible())->toBeTrue();

    $converted = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Closed',
        'converted_at' => now()->subDay(),
    ]);
    $action->record($converted);
    expect($action->isVisible())->toBeFalse();
});

it('listKanbanToggleActions(list) returns view_list primary and view_kanban gray', function () {
    $actions = LeadResource::listKanbanToggleActions('list');

    expect($actions)->toHaveCount(2);
    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('primary');
    expect($actions[0]->getUrl())->toBe(LeadResource::getUrl('index'));

    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('gray');
    expect($actions[1]->getUrl())->toBe(LeadResource::getUrl('kanban'));
});

it('listKanbanToggleActions(kanban) returns view_list gray and view_kanban primary', function () {
    $actions = LeadResource::listKanbanToggleActions('kanban');

    expect($actions)->toHaveCount(2);
    expect($actions[0]->getColor())->toBe('gray');
    expect($actions[1]->getColor())->toBe('primary');
});

it('ListLeads::getHeaderActions exposes the toggle pair plus Create', function () {
    $actions = parityInvokeHeaderActions(ListLeads::class);

    expect($actions)->toHaveCount(3);
    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('primary');
    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('gray');
    expect($actions[2])->toBeInstanceOf(CreateAction::class);
});

it('LeadKanban::getHeaderActions exposes the toggle pair plus Create pointing at the create route', function () {
    $actions = parityInvokeHeaderActions(LeadKanban::class);

    expect($actions)->toHaveCount(3);
    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('gray');
    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('primary');
    expect($actions[2])->toBeInstanceOf(CreateAction::class);
    expect($actions[2]->getUrl())->toBe(LeadResource::getUrl('create'));
});

it('row actions cover view, edit, convertToDeal, and delete', function () {
    $actions = array_values(livewire(ListLeads::class)->instance()->getTable()->getRecordActions());

    expect($actions)->toHaveCount(4);
    expect($actions[0])->toBeInstanceOf(ViewAction::class);
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[2]->getName())->toBe('convertToDeal');
    expect($actions[3])->toBeInstanceOf(DeleteAction::class);

    // Convert surface also reaches both view + edit page headers (gap area 5).
    $viewNames = array_map(fn ($a) => $a->getName(), parityInvokeHeaderActions(ViewLead::class));
    $editNames = array_map(fn ($a) => $a->getName(), parityInvokeHeaderActions(EditLead::class));
    expect($viewNames)->toContain('convertToDeal');
    expect($editNames)->toContain('convertToDeal');
});

it('doConvertToDeal stamps converted_at and persists a matching Deal', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Parity convert',
        'amount' => 7500,
        'currency' => 'USD',
    ]);

    $storedAmount = (int) $lead->fresh()->amount;
    expect($lead->fresh()->converted_at)->toBeNull();

    LeadResource::doConvertToDeal($lead->fresh());

    $lead->refresh();
    expect($lead->converted_at)->not->toBeNull();

    $deal = Deal::query()->where('title', 'Parity convert')->first();
    expect($deal)->not->toBeNull();
    expect((int) $deal->amount)->toBe($storedAmount);
    expect($deal->currency)->toBe('USD');
});
