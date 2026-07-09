<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Pages\CalendarPage;
use VentureDrake\LaravelCrmFilament\Pages\Reminders;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\TaskKanban;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;

it('declares the Calendar page at /calendar', function () {
    expect(CalendarPage::getSlug())->toBe('calendar');
});

it('exposes the moveEvent method that FullCalendar eventDrop calls', function () {
    expect(method_exists(CalendarPage::class, 'moveEvent'))->toBeTrue();
    $reflection = new ReflectionMethod(CalendarPage::class, 'moveEvent');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(3);
});

it('loads FullCalendar lazily from CDN with a window.FullCalendar guard', function () {
    $blade = file_get_contents(__DIR__ . '/../../resources/views/calendar/index.blade.php');
    expect($blade)->toContain('window.FullCalendar');
    expect($blade)->toContain('cdn.jsdelivr.net/npm/fullcalendar');
});

it('logs an activity entry from CalendarPage::moveEvent', function () {
    $source = file_get_contents((new ReflectionClass(CalendarPage::class))->getFileName());
    expect($source)->toContain('Activity::create');
    expect($source)->toContain('timelineable_type');
});

it('exposes getEventsForRange with two required string parameters', function () {
    $reflection = new ReflectionMethod(CalendarPage::class, 'getEventsForRange');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(2);

    $params = $reflection->getParameters();
    expect((string) $params[0]->getType())->toBe('string');
    expect((string) $params[1]->getType())->toBe('string');
});

it('returns FullCalendar-shaped events from getEventsForRange for a wide window', function () {
    $page = new CalendarPage;
    $page->typeFilters = ['task' => true, 'call' => true, 'meeting' => true, 'lunch' => true];
    $page->ownerFilter = null;

    $now = now();
    Task::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'wide-window-task',
        'due_at' => $now->copy()->addDays(3),
    ]);
    Call::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'wide-window-call',
        'start_at' => $now->copy()->addDays(4),
    ]);
    Meeting::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'wide-window-meeting',
        'start_at' => $now->copy()->addDays(5),
        'meetingable_type' => 'placeholder',
        'meetingable_id' => 1,
    ]);
    Lunch::create([
        'external_id' => Str::uuid()->toString(),
        'name' => 'wide-window-lunch',
        'start_at' => $now->copy()->addDays(6),
        'lunchable_type' => 'placeholder',
        'lunchable_id' => 1,
    ]);

    $start = $now->copy()->subYear()->toIso8601String();
    $end = $now->copy()->addYear()->toIso8601String();

    $events = $page->getEventsForRange($start, $end);

    // Four rows in a wide (now ± 1 year) window → four events.
    expect($events)->toHaveCount(4);

    foreach ($events as $event) {
        expect($event)->toHaveKey('id');
        expect($event)->toHaveKey('title');
        expect($event)->toHaveKey('start');
        expect($event)->toHaveKey('extendedProps');
        expect($event['extendedProps'])->toHaveKey('type');
        expect($event)->toHaveKey('backgroundColor');
    }
});

it('declares the TaskKanban as a sub-resource page of TaskResource', function () {
    expect((new ReflectionClass(TaskKanban::class))->getStaticPropertyValue('resource'))->toBe(TaskResource::class);
    expect(is_subclass_of(TaskKanban::class, ResourcePage::class))->toBeTrue();
});

it('registers the kanban route on the Tasks resource', function () {
    expect(TaskResource::getPages())->toHaveKey('kanban');
});

it('exposes moveTask on TaskKanban', function () {
    $reflection = new ReflectionMethod(TaskKanban::class, 'moveTask');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(2);
});

it('groups tasks into four status columns', function () {
    $blade = file_get_contents(__DIR__ . '/../../resources/views/tasks/kanban.blade.php');
    foreach (['open', 'today', 'overdue', 'completed'] as $status) {
        expect($blade)->toContain("'{$status}' =>");
    }
    expect($blade)->toContain('data-status="{{ $status }}"');
});

it('declares the Reminders page in the Settings navigation group at /reminders', function () {
    $nav = (new ReflectionClass(Reminders::class))->getStaticPropertyValue('navigationGroup');
    expect($nav)->toBe('Settings');
    expect(Reminders::getSlug())->toBe('reminders');
});

it('persists Reminders preferences as reminders.{user_id}.{type}.{hours_before}', function () {
    $source = file_get_contents((new ReflectionClass(Reminders::class))->getFileName());
    expect($source)->toContain('reminders.');
    expect($source)->toContain("'task' => 'Tasks'");
    expect($source)->toContain("'call' => 'Calls'");
    expect($source)->toContain("'meeting' => 'Meetings'");
    expect($source)->toContain("'lunch' => 'Lunches'");
});

it('registers the CalendarPage on the panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    expect($panel->getPages())->toContain(CalendarPage::class);
});
