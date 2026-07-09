<?php

use VentureDrake\LaravelCrmFilament\Pages\CalendarPage;

it('declares public updatedOwnerFilter() and updatedTypeFilters() no-arg void methods', function () {
    foreach (['updatedOwnerFilter', 'updatedTypeFilters'] as $method) {
        expect(method_exists(CalendarPage::class, $method))->toBeTrue();

        $reflection = new ReflectionMethod(CalendarPage::class, $method);
        expect($reflection->isPublic())->toBeTrue();
        expect($reflection->getNumberOfRequiredParameters())->toBe(0);
        expect($reflection->getNumberOfParameters())->toBe(0);
        expect((string) $reflection->getReturnType())->toBe('void');

        // Declared locally on CalendarPage, not inherited from a Livewire base class.
        expect($reflection->getDeclaringClass()->getName())->toBe(CalendarPage::class);
    }
});

it('bodies dispatch the calendar-refetch browser-side event', function () {
    $source = file_get_contents((new ReflectionClass(CalendarPage::class))->getFileName());

    // Two dispatch call sites (one per hook), both emitting the AC-named event.
    expect(substr_count($source, "\$this->dispatch('calendar-refetch')"))->toBe(2);
});

it('leaves the pre-existing moveEvent, getOwners, getEventsForRange, and getNavigationGroup signatures untouched', function () {
    foreach ([
        'moveEvent' => [3, false],
        'getOwners' => [0, false],
        'getEventsForRange' => [2, false],
        'getNavigationGroup' => [0, true],
    ] as $method => [$requiredParams, $expectStatic]) {
        $reflection = new ReflectionMethod(CalendarPage::class, $method);
        expect($reflection->isPublic())->toBeTrue();
        expect($reflection->getNumberOfRequiredParameters())->toBe($requiredParams);
        expect($reflection->isStatic())->toBe($expectStatic);
    }
});
