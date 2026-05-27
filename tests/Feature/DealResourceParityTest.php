<?php

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkLostAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealMarkWonAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\Concerns\HasDealReopenAction;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\DealKanban;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\EditDeal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ListDeals;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ViewDeal;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Deal Parity Tester',
        'email' => 'deal-parity-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

function dealParityInvokeHeaderActions(string $page): array
{
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    return $method->invoke($instance);
}

it('table registers the exact parity column order', function () {
    $columns = livewire(ListDeals::class)->instance()->getTable()->getColumns();
    $names = array_values(array_map(fn ($c) => $c->getName(), $columns));

    expect($names)->toBe([
        'created_at',
        'deal_id',
        'title',
        'labels.name',
        'amount',
        'person.name',
        'organization.name',
        'expected_close',
        'pipelineStage.name',
        'ownerUser.name',
    ]);
});

it('table created_at column uses ->since() and labels uses ->limitList(3)', function () {
    $columns = livewire(ListDeals::class)->instance()->getTable()->getColumns();

    expect($columns['labels.name']->isBadge())->toBeTrue();

    $source = file_get_contents(__DIR__ . '/../../src/Resources/Deals/DealResource.php');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain('->since()');
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('labels.name')");
    expect($source)->toContain('->limitList(3)');
});

it('table registers owner, labels, pipeline_stage filters with the expected config', function () {
    $filters = livewire(ListDeals::class)->instance()->getTable()->getFilters();

    $byName = [];
    foreach ($filters as $filter) {
        $byName[$filter->getName()] = $filter;
    }

    expect(array_keys($byName))->toContain('user_owner_id', 'labels', 'pipeline_stage_id');

    /** @var SelectFilter $owner */
    $owner = $byName['user_owner_id'];
    /** @var SelectFilter $labels */
    $labels = $byName['labels'];
    /** @var SelectFilter $stage */
    $stage = $byName['pipeline_stage_id'];

    expect($owner->isMultiple())->toBeTrue();
    expect($labels->isMultiple())->toBeTrue();
    expect($stage->isMultiple())->toBeFalse();

    expect($owner->getRelationshipName())->toBe('ownerUser');
    expect($labels->getRelationshipName())->toBe('labels');
});

it('row actions are buttons in View, Edit, Delete order', function () {
    $actions = array_values(livewire(ListDeals::class)->instance()->getTable()->getRecordActions());

    expect($actions)->toHaveCount(3);
    expect($actions[0])->toBeInstanceOf(ViewAction::class);
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[2])->toBeInstanceOf(DeleteAction::class);

    foreach ($actions as $action) {
        expect($action->isButton())->toBeTrue();
    }
});

it('listKanbanToggleActions(list) returns view_list primary and view_kanban gray', function () {
    $actions = DealResource::listKanbanToggleActions('list');

    expect($actions)->toHaveCount(2);
    expect($actions[0]->getName())->toBe('view_list');
    expect($actions[0]->getColor())->toBe('primary');
    expect($actions[0]->getUrl())->toBe(DealResource::getUrl('index'));

    expect($actions[1]->getName())->toBe('view_kanban');
    expect($actions[1]->getColor())->toBe('gray');
    expect($actions[1]->getUrl())->toBe(DealResource::getUrl('kanban'));
});

it('ListDeals and DealKanban headers expose the toggle pair and a Create action', function () {
    $listActions = dealParityInvokeHeaderActions(ListDeals::class);
    expect($listActions)->toHaveCount(3);
    expect($listActions[0]->getName())->toBe('view_list');
    expect($listActions[0]->getColor())->toBe('primary');
    expect($listActions[1]->getName())->toBe('view_kanban');
    expect($listActions[1]->getColor())->toBe('gray');
    expect($listActions[2])->toBeInstanceOf(CreateAction::class);

    $kanbanActions = dealParityInvokeHeaderActions(DealKanban::class);
    expect($kanbanActions)->toHaveCount(3);
    expect($kanbanActions[0]->getColor())->toBe('gray');
    expect($kanbanActions[1]->getColor())->toBe('primary');
    expect($kanbanActions[2])->toBeInstanceOf(CreateAction::class);
    expect($kanbanActions[2]->getUrl())->toBe(DealResource::getUrl('create'));
});

it('ListDeals no longer exposes getTabs entries', function () {
    $instance = new ListDeals;
    expect(method_exists($instance, 'getTabs'))->toBeTrue();
    expect($instance->getTabs())->toBe([]);
});

it('markWon and markLost are hidden on closed deals, visible on open deals; reopen flips the other way', function () {
    $open = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Open deal',
    ]);
    $closed = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Closed deal',
        'closed_at' => now()->subDay(),
        'closed_status' => 'won',
    ]);

    $markWon = DealResource::markWonAction();
    $markLost = DealResource::markLostAction();
    $reopen = DealResource::reopenAction();

    $markWon->record($open);
    expect($markWon->isVisible())->toBeTrue();
    $markWon->record($closed);
    expect($markWon->isVisible())->toBeFalse();

    $markLost->record($open);
    expect($markLost->isVisible())->toBeTrue();
    $markLost->record($closed);
    expect($markLost->isVisible())->toBeFalse();

    $reopen->record($open);
    expect($reopen->isVisible())->toBeFalse();
    $reopen->record($closed);
    expect($reopen->isVisible())->toBeTrue();

    expect($markWon)->toBeInstanceOf(Action::class);
    expect($markLost)->toBeInstanceOf(Action::class);
    expect($reopen)->toBeInstanceOf(Action::class);

    expect($markWon->getName())->toBe('markWon');
    expect($markLost->getName())->toBe('markLost');
    expect($reopen->getName())->toBe('reopen');
});

it('markWon persists closed_status=won and closed_at', function () {
    $deal = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Win me',
    ]);

    $action = DealResource::markWonAction();
    $action->record($deal);
    $action->call();

    $deal->refresh();
    expect($deal->closed_status)->toBe('won');
    expect($deal->closed_at)->not->toBeNull();
});

it('markLost persists closed_status=lost and closed_at', function () {
    $deal = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lose me',
    ]);

    $action = DealResource::markLostAction();
    $action->record($deal);
    $action->call();

    $deal->refresh();
    expect($deal->closed_status)->toBe('lost');
    expect($deal->closed_at)->not->toBeNull();
});

it('reopen clears closed_status and closed_at', function () {
    $deal = Deal::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Reopen me',
        'closed_at' => now()->subDay(),
        'closed_status' => 'lost',
    ]);

    $action = DealResource::reopenAction();
    $action->record($deal);
    $action->call();

    $deal->refresh();
    expect($deal->closed_status)->toBeNull();
    expect($deal->closed_at)->toBeNull();
});

it('ViewDeal and EditDeal headers include the three workflow actions and the workflow concerns are applied', function () {
    $viewActions = dealParityInvokeHeaderActions(ViewDeal::class);
    $viewNames = array_map(fn ($a) => $a->getName(), $viewActions);
    expect($viewNames)->toContain('markWon', 'markLost', 'reopen');

    $editActions = dealParityInvokeHeaderActions(EditDeal::class);
    $editNames = array_map(fn ($a) => $a->getName(), $editActions);
    expect($editNames)->toContain('markWon', 'markLost', 'reopen');

    $viewUses = class_uses_recursive(ViewDeal::class);
    expect($viewUses)->toContain(HasDealMarkWonAction::class);
    expect($viewUses)->toContain(HasDealMarkLostAction::class);
    expect($viewUses)->toContain(HasDealReopenAction::class);

    $editUses = class_uses_recursive(EditDeal::class);
    expect($editUses)->toContain(HasDealMarkWonAction::class);
    expect($editUses)->toContain(HasDealMarkLostAction::class);
    expect($editUses)->toContain(HasDealReopenAction::class);
});
