<?php

use Filament\Resources\RelationManagers\RelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedOrganizationsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\RelatedPeopleRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ViewOrganization;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ViewPerson;

/**
 * Structural coverage for the RelatedPeople/RelatedOrganizations
 * RelationManagers surfaced on the Person and Organization view pages.
 * A fuller Livewire round-trip test (add + remove reciprocal contact
 * rows) is not attempted here — these RMs live inside a Livewire-mounted
 * Filament infolist column so exercising them requires the same
 * hot-patch subclass pattern LeadNotes* tests use. Structural asserts
 * lock in the wiring; behavioral coverage lives in the reciprocal-link
 * unit-of-work tests when they land.
 */
dataset('relatedContactRms', [
    'RelatedPeopleRelationManager' => [
        RelatedPeopleRelationManager::class,
        'relatedPeopleContacts',
        'Related people',
        'person_id',
    ],
    'RelatedOrganizationsRelationManager' => [
        RelatedOrganizationsRelationManager::class,
        'relatedOrganizationContacts',
        'Related organizations',
        'organization_id',
    ],
]);

it('extends Filament\'s RelationManager base class', function (string $rm): void {
    expect(is_subclass_of($rm, RelationManager::class))->toBeTrue();
})->with('relatedContactRms');

it('declares the correct relationship name via static property', function (string $rm, string $relationship): void {
    $ref = new ReflectionClass($rm);
    expect($ref->getStaticPropertyValue('relationship'))->toBe($relationship);
})->with('relatedContactRms');

it('sets a human-readable title on the relation manager', function (string $rm, string $relationship, string $title): void {
    $ref = new ReflectionClass($rm);
    expect($ref->getStaticPropertyValue('title'))->toBe($title);
})->with('relatedContactRms');

it('mounts both related-contact managers on ViewPerson via Livewire schema components', function (): void {
    $ref = new ReflectionMethod(ViewPerson::class, 'getLeftColumnComponents');
    $ref->setAccessible(true);

    // Method reference alone; we don't invoke because the record isn't set.
    // Assert via source that both managers are registered on the page.
    $source = file_get_contents(
        (new ReflectionClass(ViewPerson::class))->getFileName(),
    );

    expect($source)->toContain('RelatedPeopleRelationManager::class');
    expect($source)->toContain('RelatedOrganizationsRelationManager::class');
});

it('mounts both related-contact managers on ViewOrganization via Livewire schema components', function (): void {
    $source = file_get_contents(
        (new ReflectionClass(ViewOrganization::class))->getFileName(),
    );

    expect($source)->toContain('RelatedPeopleRelationManager::class');
    expect($source)->toContain('RelatedOrganizationsRelationManager::class');
});
