<?php

use VentureDrake\LaravelCrmFilament\Resources\Activities\ActivityResource;
use VentureDrake\LaravelCrmFilament\Resources\Calls\CallResource;
use VentureDrake\LaravelCrmFilament\Resources\Files\FileResource;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\LunchResource;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\MeetingResource;
use VentureDrake\LaravelCrmFilament\Resources\Notes\NoteResource;

dataset('perTypeActivityResources', [
    'NoteResource' => [NoteResource::class],
    'CallResource' => [CallResource::class],
    'MeetingResource' => [MeetingResource::class],
    'LunchResource' => [LunchResource::class],
    'FileResource' => [FileResource::class],
    'ActivityResource' => [ActivityResource::class],
]);

it('declares shouldRegisterNavigation returning false', function (string $resource) {
    expect($resource::shouldRegisterNavigation())->toBeFalse();
})->with('perTypeActivityResources');

it('declares shouldRegisterNavigation locally on the class', function (string $resource) {
    $ref = new ReflectionMethod($resource, 'shouldRegisterNavigation');
    expect($ref->getDeclaringClass()->getName())->toBe($resource);
    expect($ref->isPublic())->toBeTrue();
    expect($ref->isStatic())->toBeTrue();
})->with('perTypeActivityResources');

it('still registers the view page route', function (string $resource) {
    expect($resource::getPages())->toHaveKey('view');
})->with('perTypeActivityResources');

it('still registers the kanban page route on CallResource', function () {
    expect(CallResource::getPages())->toHaveKey('kanban');
});

it('still registers the index page route', function (string $resource) {
    expect($resource::getPages())->toHaveKey('index');
})->with('perTypeActivityResources');
