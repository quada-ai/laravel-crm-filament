<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('LeadResource::getUrl substitutes the external_id, not the integer PK', function () {
    // Filament v5 reads getRecordRouteKeyName() during binding RESOLUTION but
    // not during URL generation. Without LeadResource's own getUrl() override
    // the route() helper substitutes $lead->getRouteKey() (the integer PK) and
    // the resolver then 404s when it looks up `external_id = '<pk>'` in the
    // host app — which was the reported bug on the lead show + edit pages.
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'URL binding regression',
    ]);

    $extId = $lead->fresh()->external_id;

    expect(LeadResource::getUrl('view', ['record' => $lead]))
        ->toContain($extId)
        ->not->toEndWith('/' . $lead->id);

    expect(LeadResource::getUrl('edit', ['record' => $lead]))
        ->toContain($extId);
});

it('LeadResource::getUrl leaves scalar record params alone', function () {
    // When the caller already passes the external_id string, getUrl() must
    // not double-resolve it.
    $url = LeadResource::getUrl('view', ['record' => 'pre-resolved-uuid']);

    expect($url)->toContain('pre-resolved-uuid');
});
