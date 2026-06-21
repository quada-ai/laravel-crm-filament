<?php

use VentureDrake\LaravelCrmFilament\RelationManagers\ActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadActivitiesRelationManager;

it('extends ActivitiesRelationManager', function () {
    expect(is_subclass_of(LeadActivitiesRelationManager::class, ActivitiesRelationManager::class))->toBeTrue();
});

it('overrides the $view property to point at the lead-activity Blade template', function () {
    $ref = new ReflectionClass(LeadActivitiesRelationManager::class);
    $prop = $ref->getProperty('view');
    $prop->setAccessible(true);

    expect($prop->getDeclaringClass()->getName())->toBe(LeadActivitiesRelationManager::class);

    $rm = $ref->newInstanceWithoutConstructor();
    expect($prop->getValue($rm))->toBe('laravel-crm-filament::lead-activity');
});

it('inherits read-only contract from ActivitiesRelationManager', function () {
    $rm = (new ReflectionClass(LeadActivitiesRelationManager::class))->newInstanceWithoutConstructor();
    expect($rm->isReadOnly())->toBeTrue();
});

it('the lead-activity Blade view contains the expected timeline markers', function () {
    $bladePath = dirname(__DIR__, 2) . '/resources/views/lead-activity.blade.php';
    expect(file_exists($bladePath))->toBeTrue();

    $blade = file_get_contents($bladePath);

    // Wrapper class scopes the shared CSS custom properties.
    expect($blade)->toContain('class="crm-lead-activity"');

    // Loop over the owner's timelineActivities, newest first.
    expect($blade)->toContain('$this->getOwnerRecord()');
    expect($blade)->toContain('timelineActivities()');
    expect($blade)->toContain("->orderBy('created_at', 'desc')");
    expect($blade)->toContain('@forelse');
    expect($blade)->toContain('@empty');

    // Timeline structural markers (rail + bullet + connector + body).
    expect($blade)->toContain('crm-timeline-item');
    expect($blade)->toContain('crm-timeline-rail');
    expect($blade)->toContain('crm-timeline-bullet');
    expect($blade)->toContain('crm-timeline-connector');
    expect($blade)->toContain('crm-timeline-body');
    expect($blade)->toContain('crm-timeline-title');
    expect($blade)->toContain('crm-timeline-subtitle');

    // Shared partial @include + no inline @once block (regression guard).
    expect($blade)->toContain("@include('laravel-crm-filament::partials.lead-card-styles')");
    expect($blade)->not->toContain('@once');

    // Empty state.
    expect($blade)->toContain('No activity yet');
});

it('the shared lead-card-styles partial declares timeline + .crm-lead-activity scoping', function () {
    $partial = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/lead-card-styles.blade.php');

    // Wrapper class participates in the existing CSS custom-property scope.
    expect($partial)->toContain('.crm-lead-activity');
    expect($partial)->toContain('html.dark .crm-lead-activity');

    // Timeline-specific selectors.
    expect($partial)->toContain('.crm-timeline-item');
    expect($partial)->toContain('.crm-timeline-rail');
    expect($partial)->toContain('.crm-timeline-bullet');
    expect($partial)->toContain('.crm-timeline-connector');
    expect($partial)->toContain('.crm-timeline-body');
    expect($partial)->toContain('.crm-timeline-title');
    expect($partial)->toContain('.crm-timeline-subtitle');
});
