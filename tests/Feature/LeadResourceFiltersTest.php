<?php

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ListLeads;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Filter Tester',
        'email' => 'lead-filter-tester' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

/**
 * @return array<string, SelectFilter>
 */
function leadTableFilters(): array
{
    /** @var ListLeads $instance */
    $instance = livewire(ListLeads::class)->instance();

    $filters = $instance->getTable()->getFilters();

    $out = [];
    foreach ($filters as $filter) {
        $out[$filter->getName()] = $filter;
    }

    return $out;
}

it('registers the four filters in the drawer', function () {
    $names = array_keys(leadTableFilters());

    expect($names)->toContain('user_owner_id', 'labels', 'lead_source_id', 'pipeline_id', 'pipeline_stage_id');
});

it('marks owner, labels, and lead_source filters as multi-select', function () {
    $filters = leadTableFilters();

    expect($filters['user_owner_id']->isMultiple())->toBeTrue();
    expect($filters['labels']->isMultiple())->toBeTrue();
    expect($filters['lead_source_id']->isMultiple())->toBeTrue();
});

it('makes the owner filter searchable and preloaded', function () {
    $filter = leadTableFilters()['user_owner_id'];

    expect($filter->getSearchable())->toBeTrue();
    expect($filter->isPreloaded())->toBeTrue();
});

it('marks the labels filter as preloaded', function () {
    $filter = leadTableFilters()['labels'];

    expect($filter->isPreloaded())->toBeTrue();
});

it('binds owner and labels filters to their Eloquent relationships', function () {
    $filters = leadTableFilters();

    expect($filters['user_owner_id']->getRelationshipName())->toBe('ownerUser');
    expect($filters['labels']->getRelationshipName())->toBe('labels');
});

it('narrows the table when filtering by owner', function () {
    $owner = User::create([
        'name' => 'Owner A',
        'email' => 'owner-a-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    $mine = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Owned by A',
        'user_owner_id' => $owner->id,
    ]);

    $other = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Unowned',
    ]);

    livewire(ListLeads::class)
        ->filterTable('user_owner_id', [$owner->id])
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});

it('narrows the table when filtering by lead source', function () {
    $source = LeadSource::create(['name' => 'Referral']);

    $matched = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Has source',
        'lead_source_id' => $source->id,
    ]);

    $other = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'No source',
    ]);

    livewire(ListLeads::class)
        ->filterTable('lead_source_id', [$source->id])
        ->assertCanSeeTableRecords([$matched])
        ->assertCanNotSeeTableRecords([$other]);
});

it('narrows the table when filtering by label', function () {
    $label = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Hot',
        'hex' => 'ff0000',
    ]);

    $tagged = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Tagged lead',
    ]);
    $tagged->labels()->attach($label->id);

    $untagged = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Untagged lead',
    ]);

    livewire(ListLeads::class)
        ->filterTable('labels', [$label->id])
        ->assertCanSeeTableRecords([$tagged])
        ->assertCanNotSeeTableRecords([$untagged]);
});

it('uses translation keys for filter labels', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Leads/LeadResource.php');

    expect($source)->toContain("SelectFilter::make('user_owner_id')");
    expect($source)->toContain("SelectFilter::make('labels')");
    expect($source)->toContain("SelectFilter::make('lead_source_id')");
    expect($source)->toContain('labels.fields.owner');
    expect($source)->toContain('labels.fields.labels');
    expect($source)->toContain('labels.sales.lead_source');
});
