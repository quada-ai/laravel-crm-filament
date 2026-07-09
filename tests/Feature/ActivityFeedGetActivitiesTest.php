<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Pages\ActivityFeed;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

beforeEach(function (): void {
    RoleSeeder::seed();
});

it('declares a public getActivities() method returning LengthAwarePaginator', function (): void {
    $ref = new ReflectionClass(ActivityFeed::class);
    expect($ref->hasMethod('getActivities'))->toBeTrue();

    $method = $ref->getMethod('getActivities');
    expect($method->isPublic())->toBeTrue();
    expect($method->getDeclaringClass()->getName())->toBe(ActivityFeed::class);
    expect($method->getReturnType()?->getName())->toBe(LengthAwarePaginator::class);
    expect($method->getNumberOfRequiredParameters())->toBe(0);
});

it('declares a private recordableTypeMap() helper on the Page', function (): void {
    $ref = new ReflectionClass(ActivityFeed::class);
    expect($ref->hasMethod('recordableTypeMap'))->toBeTrue();

    $method = $ref->getMethod('recordableTypeMap');
    expect($method->isPrivate())->toBeTrue();
    expect($method->getDeclaringClass()->getName())->toBe(ActivityFeed::class);
    expect($method->getReturnType()?->getName())->toBe('array');
});

it('recordableTypeMap() matches the LogsCrmActivity slug -> class contract', function (): void {
    $method = new ReflectionMethod(ActivityFeed::class, 'recordableTypeMap');
    $method->setAccessible(true);
    $page = (new ReflectionClass(ActivityFeed::class))->newInstanceWithoutConstructor();
    $map = $method->invoke($page);

    expect($map)->toBe([
        'notes' => Note::class,
        'tasks' => Task::class,
        'calls' => Call::class,
        'meetings' => Meeting::class,
        'lunches' => Lunch::class,
        'files' => File::class,
    ]);
});

it('returns a LengthAwarePaginator with page size 25 from Activity::query()->latest()->paginate(25)', function (): void {
    $user = User::create([
        'name' => 'Feed Viewer',
        'email' => 'feed-viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    for ($i = 0; $i < 3; $i++) {
        Activity::create([
            'external_id' => (string) Str::uuid(),
            'log_name' => 'crm',
            'event' => 'created',
            'causeable_type' => $user->getMorphClass(),
            'causeable_id' => $user->id,
            'recordable_type' => Note::class,
            'recordable_id' => $i + 1,
        ]);
    }

    $page = new ActivityFeed;
    $page->scope = 'all';
    $page->tab = 'all';

    $paginator = $page->getActivities();

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($paginator->perPage())->toBe(25);
    expect($paginator->total())->toBe(3);
});

it("scope === 'mine' restricts to the current user's causeable_id + causeable_type", function (): void {
    $viewer = User::create([
        'name' => 'Viewer',
        'email' => 'viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $other = User::create([
        'name' => 'Other',
        'email' => 'other-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($viewer);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Note::class,
        'recordable_id' => 1,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $other->getMorphClass(),
        'causeable_id' => $other->id,
        'recordable_type' => Note::class,
        'recordable_id' => 2,
    ]);

    $page = new ActivityFeed;
    $page->scope = 'mine';
    $page->tab = 'all';

    $paginator = $page->getActivities();

    expect($paginator->total())->toBe(1);
    $only = $paginator->items()[0];
    expect((int) $only->causeable_id)->toBe($viewer->id);
    expect($only->causeable_type)->toBe($viewer->getMorphClass());
});

it("scope === 'all' does not apply the causeable filter", function (): void {
    $viewer = User::create([
        'name' => 'Viewer',
        'email' => 'viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $other = User::create([
        'name' => 'Other',
        'email' => 'other-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($viewer);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Note::class,
        'recordable_id' => 1,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $other->getMorphClass(),
        'causeable_id' => $other->id,
        'recordable_type' => Note::class,
        'recordable_id' => 2,
    ]);

    $page = new ActivityFeed;
    $page->scope = 'all';
    $page->tab = 'all';

    expect($page->getActivities()->total())->toBe(2);
});

it("tab !== 'all' restricts recordable_type to the mapped class per LogsCrmActivity", function (): void {
    $user = User::create([
        'name' => 'Feed Viewer',
        'email' => 'feed-viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    $recordables = [
        Note::class,
        Task::class,
        Call::class,
        Meeting::class,
        Lunch::class,
        File::class,
    ];

    foreach ($recordables as $i => $class) {
        Activity::create([
            'external_id' => (string) Str::uuid(),
            'log_name' => 'crm',
            'event' => 'created',
            'causeable_type' => $user->getMorphClass(),
            'causeable_id' => $user->id,
            'recordable_type' => $class,
            'recordable_id' => $i + 1,
        ]);
    }

    $page = new ActivityFeed;
    $page->scope = 'all';

    $expectations = [
        'notes' => Note::class,
        'tasks' => Task::class,
        'calls' => Call::class,
        'meetings' => Meeting::class,
        'lunches' => Lunch::class,
        'files' => File::class,
    ];

    foreach ($expectations as $tab => $class) {
        $page->tab = $tab;
        $paginator = $page->getActivities();

        expect($paginator->total())->toBe(1);
        expect($paginator->items()[0]->recordable_type)->toBe($class);
    }
});

it("tab === 'all' does not apply the recordable_type filter", function (): void {
    $user = User::create([
        'name' => 'Feed Viewer',
        'email' => 'feed-viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($user);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $user->getMorphClass(),
        'causeable_id' => $user->id,
        'recordable_type' => Note::class,
        'recordable_id' => 1,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $user->getMorphClass(),
        'causeable_id' => $user->id,
        'recordable_type' => Task::class,
        'recordable_id' => 2,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $user->getMorphClass(),
        'causeable_id' => $user->id,
        'recordable_type' => Call::class,
        'recordable_id' => 3,
    ]);

    $page = new ActivityFeed;
    $page->scope = 'all';
    $page->tab = 'all';

    expect($page->getActivities()->total())->toBe(3);
});

it('composes scope + tab filters together', function (): void {
    $viewer = User::create([
        'name' => 'Viewer',
        'email' => 'viewer-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $other = User::create([
        'name' => 'Other',
        'email' => 'other-' . Str::random(6) . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    auth()->login($viewer);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Note::class,
        'recordable_id' => 1,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $viewer->getMorphClass(),
        'causeable_id' => $viewer->id,
        'recordable_type' => Task::class,
        'recordable_id' => 2,
    ]);
    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'event' => 'created',
        'causeable_type' => $other->getMorphClass(),
        'causeable_id' => $other->id,
        'recordable_type' => Note::class,
        'recordable_id' => 3,
    ]);

    $page = new ActivityFeed;
    $page->scope = 'mine';
    $page->tab = 'notes';

    $paginator = $page->getActivities();
    expect($paginator->total())->toBe(1);
    expect($paginator->items()[0]->recordable_type)->toBe(Note::class);
    expect((int) $paginator->items()[0]->causeable_id)->toBe($viewer->id);
});
