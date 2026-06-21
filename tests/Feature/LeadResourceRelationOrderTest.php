<?php

use VentureDrake\LaravelCrmFilament\RelationManagers\ActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadTasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('LeadResource::getRelations() contains both LeadLunchesRelationManager and ActivitiesRelationManager', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->toContain(LeadLunchesRelationManager::class)
        ->and($relations)->toContain(ActivitiesRelationManager::class);
});

it('LeadResource::getRelations() preserves the six previously-registered RMs', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->toContain(LeadNotesRelationManager::class)
        ->and($relations)->toContain(LeadTasksRelationManager::class)
        ->and($relations)->toContain(LeadCallsRelationManager::class)
        ->and($relations)->toContain(LeadMeetingsRelationManager::class)
        ->and($relations)->toContain(LeadFilesRelationManager::class);
});

it('LeadResource::getRelations() returns the AC tab order: Activity / Notes / Tasks / Calls / Meetings / Lunches / Files', function () {
    expect(LeadResource::getRelations())->toBe([
        ActivitiesRelationManager::class,
        LeadNotesRelationManager::class,
        LeadTasksRelationManager::class,
        LeadCallsRelationManager::class,
        LeadMeetingsRelationManager::class,
        LeadLunchesRelationManager::class,
        LeadFilesRelationManager::class,
    ]);
});
