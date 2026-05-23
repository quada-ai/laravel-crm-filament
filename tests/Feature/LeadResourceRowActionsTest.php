<?php

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Row Actions Tester',
        'email' => 'lead-row-actions-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<int, Action>
 */
function leadRecordActions(): array
{
    /** @var ListLeads $instance */
    $instance = livewire(ListLeads::class)->instance();

    return array_values($instance->getTable()->getRecordActions());
}

it('registers four row actions in the order View, Edit, Convert, Delete', function () {
    $actions = leadRecordActions();

    expect($actions)->toHaveCount(4);
    expect($actions[0])->toBeInstanceOf(ViewAction::class);
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[2]->getName())->toBe('convertToDeal');
    expect($actions[3])->toBeInstanceOf(DeleteAction::class);
});

it('hides the Convert row action when the lead is already converted', function () {
    $actions = leadRecordActions();
    /** @var Action $convert */
    $convert = $actions[2];

    $open = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Still open',
    ]);

    $convert->record($open);
    expect($convert->isVisible())->toBeTrue();

    $closed = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Already converted',
        'converted_at' => now()->subHour(),
    ]);

    $convert->record($closed);
    expect($convert->isVisible())->toBeFalse();
});

it('requires confirmation on the Delete row action', function () {
    $actions = leadRecordActions();
    /** @var DeleteAction $delete */
    $delete = $actions[3];

    expect($delete->isConfirmationRequired())->toBeTrue();
});

it('runs the Convert row action to create a Deal and stamp converted_at', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Convert row',
        'amount' => 2500,
        'currency' => 'USD',
    ])->fresh();

    expect($lead->converted_at)->toBeNull();

    $actions = leadRecordActions();
    /** @var Action $convert */
    $convert = $actions[2];
    $convert->record($lead);
    $convert->call();

    expect($lead->fresh()->converted_at)->not->toBeNull();
    expect(Deal::query()->where('title', 'Convert row')->exists())->toBeTrue();
});

it('LeadResource source registers the convert and delete row actions', function () {
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/src/Resources/Leads/LeadResource.php'
    );

    expect($source)->toContain('static::convertAction()');
    expect($source)->toContain('Actions\\DeleteAction::make()');
    expect($source)->toContain('->requiresConfirmation()');
});
