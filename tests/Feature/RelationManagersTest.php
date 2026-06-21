<?php

use VentureDrake\LaravelCrmFilament\RelationManagers\CallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadTasksRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\MeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\NotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\TasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\RecipientsRelationManager as EmailRecipients;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager as SmsRecipients;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;

dataset('contactActivityParents', [
    'Deal' => [DealResource::class],
    'Person' => [PersonResource::class],
    'Organization' => [OrganizationResource::class],
]);

it('attaches Notes/Tasks/Calls/Meetings RMs to all contact-activity parents', function (string $resource) {
    $relations = $resource::getRelations();
    expect($relations)->toContain(NotesRelationManager::class);
    expect($relations)->toContain(TasksRelationManager::class);
    expect($relations)->toContain(CallsRelationManager::class);
    expect($relations)->toContain(MeetingsRelationManager::class);
})->with('contactActivityParents');

it('attaches LeadNotesRelationManager + LeadTasksRelationManager (subclasses of Notes/Tasks RMs) plus Calls/Meetings RMs to LeadResource', function () {
    $relations = LeadResource::getRelations();
    expect($relations)->toContain(LeadNotesRelationManager::class);
    expect($relations)->not->toContain(NotesRelationManager::class);
    expect($relations)->toContain(LeadTasksRelationManager::class);
    expect($relations)->not->toContain(TasksRelationManager::class);
    expect($relations)->toContain(CallsRelationManager::class);
    expect($relations)->toContain(MeetingsRelationManager::class);
});

it('uses the polymorphic relationship names that match HasCrmActivities', function () {
    $rm = new ReflectionClass(NotesRelationManager::class);
    expect($rm->getStaticPropertyValue('relationship'))->toBe('notes');

    $rm = new ReflectionClass(TasksRelationManager::class);
    expect($rm->getStaticPropertyValue('relationship'))->toBe('tasks');

    $rm = new ReflectionClass(CallsRelationManager::class);
    expect($rm->getStaticPropertyValue('relationship'))->toBe('calls');

    $rm = new ReflectionClass(MeetingsRelationManager::class);
    expect($rm->getStaticPropertyValue('relationship'))->toBe('meetings');
});

it('attaches Recipients RM to both campaign resources', function () {
    expect(EmailCampaignResource::getRelations())->toContain(EmailRecipients::class);
    expect(SmsCampaignResource::getRelations())->toContain(SmsRecipients::class);
});
