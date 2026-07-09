<?php

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Pages\ActivityFeed;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    RoleSeeder::seed();
});

it('extends Filament\Pages\Page, uses the activities slug, and reports the Activity nav group', function (): void {
    expect(is_subclass_of(ActivityFeed::class, Page::class))->toBeTrue();
    expect(ActivityFeed::getSlug())->toBe('activities');

    $panel = Panel::make()->id('us006-activity-feed-nav')->default();
    $plugin = LaravelCrmPlugin::make();
    $panel->plugin($plugin);
    $plugin->register($panel);
    Filament::setCurrentPanel($panel);

    expect(ActivityFeed::getNavigationGroup())->toBe('Activity');
});

it('declares $scope and $tab as public string properties with defaults and #[Url] attributes', function (): void {
    $scope = new ReflectionProperty(ActivityFeed::class, 'scope');
    expect($scope->isPublic())->toBeTrue();
    expect($scope->getType()?->getName())->toBe('string');
    expect($scope->getDefaultValue())->toBe('mine');
    expect($scope->getAttributes(Url::class))->not->toBeEmpty();

    $tab = new ReflectionProperty(ActivityFeed::class, 'tab');
    expect($tab->isPublic())->toBeTrue();
    expect($tab->getType()?->getName())->toBe('string');
    expect($tab->getDefaultValue())->toBe('all');
    expect($tab->getAttributes(Url::class))->not->toBeEmpty();
});

it('filters by tab and scope end-to-end through a Livewire mount of ActivityFeed', function (): void {
    $viewer = User::create([
        'name' => 'Feed Viewer',
        'email' => 'feed-viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $other = User::create([
        'name' => 'Feed Other',
        'email' => 'feed-other-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $viewer->assignRole('Admin');
    $this->actingAs($viewer);

    // Viewer's note.
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Note::class,
        'recordable_id' => 1,
    ]);
    // Viewer's task (mixed recordable_type).
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Task::class,
        'recordable_id' => 2,
    ]);
    // Other user's call.
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $other->getMorphClass(),
        'causeable_id' => $other->id,
        'recordable_type' => Call::class,
        'recordable_id' => 3,
    ]);

    $test = livewire(ActivityFeed::class);

    // Default state (scope=mine, tab=all): the viewer's two rows only.
    $mineAll = $test->instance()->getActivities();
    expect($mineAll->total())->toBe(2);

    // setTab('notes') narrows to the viewer's single Note row.
    $test->call('setTab', 'notes');
    $mineNotes = $test->instance()->getActivities();
    expect($mineNotes->total())->toBe(1);
    expect($mineNotes->items()[0]->recordable_type)->toBe(Note::class);
    expect((int) $mineNotes->items()[0]->causeable_id)->toBe($viewer->id);

    // setScope('all') brings the other user's row back into scope; still tab=notes.
    $test->call('setScope', 'all');
    $allNotes = $test->instance()->getActivities();
    // Still 1 (only Note rows across all users).
    expect($allNotes->total())->toBe(1);

    // Widen to tab=all + scope=all -> all three rows.
    $test->call('setTab', 'all');
    $allAll = $test->instance()->getActivities();
    expect($allAll->total())->toBe(3);
});

it('ships the activity-feed Blade view with wire:click="setTab( and @foreach markers', function (): void {
    $bladePath = __DIR__ . '/../../resources/views/activity-feed.blade.php';
    expect(file_exists($bladePath))->toBeTrue();

    $blade = file_get_contents($bladePath);
    expect(str_contains($blade, 'wire:click="setTab('))->toBeTrue();
    expect(str_contains($blade, '@foreach'))->toBeTrue();
});
