<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Pages\CalendarPage;

it('exposes getEventsForRange(string $start, string $end): array', function () {
    expect(method_exists(CalendarPage::class, 'getEventsForRange'))->toBeTrue();

    $reflection = new ReflectionMethod(CalendarPage::class, 'getEventsForRange');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(2);
    expect($reflection->getNumberOfParameters())->toBe(2);

    $params = $reflection->getParameters();
    expect($params[0]->getName())->toBe('start');
    expect((string) $params[0]->getType())->toBe('string');
    expect($params[1]->getName())->toBe('end');
    expect((string) $params[1]->getType())->toBe('string');

    expect((string) $reflection->getReturnType())->toBe('array');
});

it('no longer exposes getEvents()', function () {
    expect(method_exists(CalendarPage::class, 'getEvents'))->toBeFalse();
});

it('applies Carbon::parse + whereBetween + limit(1000) to each of the four queries', function () {
    $source = file_get_contents((new ReflectionClass(CalendarPage::class))->getFileName());

    // Carbon::parse applied on both bounds.
    expect($source)->toContain('Carbon::parse($start)');
    expect($source)->toContain('Carbon::parse($end)');

    // due_at whereBetween for Task.
    expect($source)->toContain("whereBetween('due_at', [\$startCarbon, \$endCarbon])");

    // start_at whereBetween applied inside the Call/Meeting/Lunch shared loop.
    expect($source)->toContain("whereBetween('start_at', [\$startCarbon, \$endCarbon])");

    // Each of the four queries gets ->limit(1000). Two literal call sites in source:
    // one inside the task branch, one inside the shared Call/Meeting/Lunch loop (which
    // fires for each of the three types at runtime).
    expect(substr_count($source, '->limit(1000)'))->toBe(2);
});

it('preserves owner-filter when() clauses and typeFilters gating', function () {
    $source = file_get_contents((new ReflectionClass(CalendarPage::class))->getFileName());

    // Owner filter closures preserved (once inside task branch, once inside shared loop).
    expect(substr_count($source, "->when(\$this->ownerFilter, fn (\$q) => \$q->where('user_owner_id', \$this->ownerFilter))"))->toBe(2);

    // typeFilters gating preserved (task explicit guard + shared loop continue).
    expect($source)->toContain("if (\$this->typeFilters['task'] ?? false)");
    expect($source)->toContain('if (! ($this->typeFilters[$type] ?? false))');
});

it('leaves moveEvent and getOwners untouched', function () {
    // moveEvent public 3-arg signature preserved (locks the AC-mandated invariant).
    $move = new ReflectionMethod(CalendarPage::class, 'moveEvent');
    expect($move->isPublic())->toBeTrue();
    expect($move->getNumberOfRequiredParameters())->toBe(3);

    // getOwners public no-arg signature preserved.
    $owners = new ReflectionMethod(CalendarPage::class, 'getOwners');
    expect($owners->isPublic())->toBeTrue();
    expect($owners->getNumberOfRequiredParameters())->toBe(0);
});

it('returns FullCalendar-shaped events only for the requested date window', function () {
    $page = new CalendarPage;
    $page->typeFilters = ['task' => true, 'call' => true, 'meeting' => true, 'lunch' => true];
    $page->ownerFilter = null;

    // Seed rows across three time buckets: before window, inside window, after window.
    $before = '2024-06-01 12:00:00';
    $inside = '2024-07-15 12:00:00';
    $after = '2024-08-15 12:00:00';

    Task::create(['external_id' => Str::uuid()->toString(), 'name' => 'task-before', 'due_at' => $before]);
    Task::create(['external_id' => Str::uuid()->toString(), 'name' => 'task-inside', 'due_at' => $inside]);
    Task::create(['external_id' => Str::uuid()->toString(), 'name' => 'task-after', 'due_at' => $after]);

    Call::create(['external_id' => Str::uuid()->toString(), 'name' => 'call-inside', 'start_at' => $inside]);
    Meeting::create(['external_id' => Str::uuid()->toString(), 'name' => 'meeting-inside', 'start_at' => $inside, 'meetingable_type' => 'placeholder', 'meetingable_id' => 1]);
    Lunch::create(['external_id' => Str::uuid()->toString(), 'name' => 'lunch-inside', 'start_at' => $inside, 'lunchable_type' => 'placeholder', 'lunchable_id' => 1]);

    // Ask for the July window.
    $events = $page->getEventsForRange('2024-07-01', '2024-07-31');

    $titles = array_map(fn ($e) => $e['title'], $events);

    // Only rows inside the window come through.
    expect($titles)->toContain('task-inside');
    expect($titles)->toContain('call-inside');
    expect($titles)->toContain('meeting-inside');
    expect($titles)->toContain('lunch-inside');
    expect($titles)->not->toContain('task-before');
    expect($titles)->not->toContain('task-after');

    // Event shape (id/title/start/allDay/extendedProps/backgroundColor/borderColor) preserved.
    $first = $events[0];
    foreach (['id', 'title', 'start', 'allDay', 'extendedProps', 'backgroundColor', 'borderColor'] as $k) {
        expect($first)->toHaveKey($k);
    }
});

it('respects typeFilters gating inside the window', function () {
    $page = new CalendarPage;
    $page->typeFilters = ['task' => true, 'call' => false, 'meeting' => false, 'lunch' => false];
    $page->ownerFilter = null;

    Task::create(['external_id' => Str::uuid()->toString(), 'name' => 't1', 'due_at' => '2024-07-15 10:00:00']);
    Call::create(['external_id' => Str::uuid()->toString(), 'name' => 'c1', 'start_at' => '2024-07-15 11:00:00']);

    $events = $page->getEventsForRange('2024-07-01', '2024-07-31');
    $titles = array_map(fn ($e) => $e['title'], $events);

    expect($titles)->toContain('t1');
    expect($titles)->not->toContain('c1');
});
