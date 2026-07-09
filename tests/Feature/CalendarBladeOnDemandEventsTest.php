<?php

/**
 * US-003: Rewrite calendar Blade view to fetch events on demand.
 *
 * Locks the contract that the Blade view (a) no longer bakes events into the
 * page HTML via a @php header block, (b) uses FullCalendar's events() callback
 * to fan out per-range fetches through $wire.getEventsForRange, (c) drops the
 * calendar-events-bridge x-effect div entirely, and (d) registers a
 * Livewire.on('calendar-refetch', ...) listener that calls
 * this.calendar?.refetchEvents(). Preserves the toolbar wire:model.live
 * bindings, the CDN lazy-loader guard, and the eventDrop -> $wire.moveEvent
 * handler so existing regression tests (CalendarTaskKanbanRemindersTest)
 * stay green.
 */
function calendarBladeSource(): string
{
    return file_get_contents(__DIR__ . '/../../resources/views/calendar/index.blade.php');
}

it('no longer bakes events into the page via a @php $this->getEvents() header block', function () {
    $blade = calendarBladeSource();

    expect($blade)->not->toContain('$events = $this->getEvents(');
    expect($blade)->not->toContain('$events = $this->getEventsForRange(');
    expect($blade)->not->toContain('events: @js($events)');
});

it('uses a FullCalendar events() callback that fans out to $wire.getEventsForRange', function () {
    $blade = calendarBladeSource();

    expect($blade)->toContain('events: (info, success, failure) => {');
    expect($blade)->toContain('$wire.getEventsForRange(info.startStr, info.endStr)');
    expect($blade)->toContain('.then(success).catch(failure)');
});

it('removes the calendar-events-bridge x-effect div entirely', function () {
    $blade = calendarBladeSource();

    expect($blade)->not->toContain('calendar-events-bridge');
    expect($blade)->not->toContain('x-effect');
    expect($blade)->not->toContain('calendar.removeAllEvents()');
    expect($blade)->not->toContain('calendar.addEvent(');
});

it('registers a Livewire.on(calendar-refetch) listener that refetches the calendar', function () {
    $blade = calendarBladeSource();

    expect($blade)->toContain("Livewire.on('calendar-refetch'");
    expect($blade)->toContain('this.calendar?.refetchEvents()');
});

it('preserves the toolbar wire:model.live bindings on ownerFilter and typeFilters', function () {
    $blade = calendarBladeSource();

    expect($blade)->toContain('wire:model.live="ownerFilter"');
    expect($blade)->toContain('wire:model.live="typeFilters.task"');
    expect($blade)->toContain('wire:model.live="typeFilters.call"');
    expect($blade)->toContain('wire:model.live="typeFilters.meeting"');
    expect($blade)->toContain('wire:model.live="typeFilters.lunch"');
});

it('preserves the CDN lazy-loader guard and the eventDrop -> $wire.moveEvent handler', function () {
    $blade = calendarBladeSource();

    expect($blade)->toContain('window.FullCalendar');
    expect($blade)->toContain('cdn.jsdelivr.net/npm/fullcalendar');
    expect($blade)->toContain('$wire.moveEvent(externalId, type, info.event.start.toISOString())');
});
