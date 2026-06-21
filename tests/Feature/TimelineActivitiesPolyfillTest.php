<?php

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\LaravelCrmFilamentServiceProvider;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;

beforeEach(function (): void {
    RoleSeeder::seed();
});

it('exposes timelineActivities as a MorphMany on Lead', function (): void {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Polyfill probe',
    ]);

    $relation = $lead->timelineActivities();

    expect($relation)->toBeInstanceOf(MorphMany::class);
    expect($relation->getMorphType())->toBe('timelineable_type');
    expect($relation->getRelated())->toBeInstanceOf(Activity::class);
});

it('returns an Eloquent collection of activities scoped to the lead', function (): void {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Scoped probe',
    ]);

    $other = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Other lead',
    ]);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'description' => 'mine',
        'event' => 'created',
        'timelineable_type' => $lead->getMorphClass(),
        'timelineable_id' => $lead->getKey(),
    ]);

    Activity::create([
        'external_id' => (string) Str::uuid(),
        'log_name' => 'crm',
        'description' => 'theirs',
        'event' => 'created',
        'timelineable_type' => $other->getMorphClass(),
        'timelineable_id' => $other->getKey(),
    ]);

    $rows = $lead->fresh()->timelineActivities;

    expect($rows)->toHaveCount(1);
    expect($rows->first()->description)->toBe('mine');
});

it('registers the polyfill inside packageBooted alongside audits and labels', function (): void {
    $source = file_get_contents(
        (new ReflectionClass(LaravelCrmFilamentServiceProvider::class))->getFileName(),
    );

    // The polyfill is now applied via a foreach loop over timelineActivityModels()
    // so the polyfill body uses the loop variable, not the literal Lead model.
    expect($source)->toContain('static::timelineActivityModels()');
    expect($source)->toContain("\$timelineModel::resolveRelationUsing('timelineActivities'");
    expect($source)->toContain("morphMany(Activity::class, 'timelineable')");

    // Sanity check: the new polyfill sits between the audits block (above)
    // and the campaign clicks block (below) inside packageBooted.
    $auditsPos = strpos($source, "resolveRelationUsing('audits'");
    $timelinePos = strpos($source, "resolveRelationUsing('timelineActivities'");
    $clicksPos = strpos($source, "resolveRelationUsing('clicks'");

    expect($auditsPos)->toBeLessThan($timelinePos);
    expect($timelinePos)->toBeLessThan($clicksPos);
});

it('leaves the existing audits and labels polyfills unchanged', function (): void {
    $source = file_get_contents(
        (new ReflectionClass(LaravelCrmFilamentServiceProvider::class))->getFileName(),
    );

    expect($source)->toContain("Product::resolveRelationUsing('labels'");
    expect($source)->toContain("Delivery::resolveRelationUsing('labels'");
    expect($source)->toContain("resolveRelationUsing('audits'");
    expect($source)->toContain('morphMany(Audit::class, \'auditable\')');
});

it('polyfills timelineActivities on every primary model named by timelineActivityModels', function (): void {
    $models = LaravelCrmFilamentServiceProvider::timelineActivityModels();

    expect($models)->toHaveCount(9);
    expect($models)->toContain(Lead::class);
    expect($models)->toContain(Deal::class);
    expect($models)->toContain(Quote::class);
    expect($models)->toContain(Order::class);
    expect($models)->toContain(Invoice::class);
    expect($models)->toContain(PurchaseOrder::class);
    expect($models)->toContain(Delivery::class);
    expect($models)->toContain(Person::class);
    expect($models)->toContain(Organization::class);

    foreach ($models as $modelClass) {
        $instance = new $modelClass;
        $relation = $instance->timelineActivities();
        expect($relation)->toBeInstanceOf(MorphMany::class);
        expect($relation->getMorphType())->toBe('timelineable_type');
        expect($relation->getRelated())->toBeInstanceOf(Activity::class);
    }
});
