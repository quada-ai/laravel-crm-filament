<?php

use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('LeadResource::getRelations() contains both CrmLunchesRelationManager and ActivitiesRelationManager', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->toContain(CrmLunchesRelationManager::class)
        ->and($relations)->toContain(CrmActivitiesRelationManager::class);
});

it('LeadResource::getRelations() preserves the six previously-registered RMs', function () {
    $relations = LeadResource::getRelations();

    expect($relations)->toContain(CrmNotesRelationManager::class)
        ->and($relations)->toContain(CrmTasksRelationManager::class)
        ->and($relations)->toContain(CrmCallsRelationManager::class)
        ->and($relations)->toContain(CrmMeetingsRelationManager::class)
        ->and($relations)->toContain(CrmFilesRelationManager::class);
});

it('LeadResource::getRelations() returns the AC tab order: Activity / Notes / Tasks / Calls / Meetings / Lunches / Files', function () {
    expect(LeadResource::getRelations())->toBe([
        CrmActivitiesRelationManager::class,
        CrmNotesRelationManager::class,
        CrmTasksRelationManager::class,
        CrmCallsRelationManager::class,
        CrmMeetingsRelationManager::class,
        CrmLunchesRelationManager::class,
        CrmFilesRelationManager::class,
    ]);
});
