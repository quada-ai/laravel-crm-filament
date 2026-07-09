<?php

use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;

it('does NOT declare a local content() override on ViewLead', function () {
    // Reverted the custom 2-col Grid content() override that landed in US-004 of
    // the lead show-page series because nesting getRelationManagersContentComponent()
    // inside a Grid columnSpan broke the RM tab strip's Livewire tab switching.
    // ViewLead now falls back to Filament's default ViewRecord::content() which
    // renders infolist + RM tabs vertically and switches tabs correctly.
    $method = new ReflectionMethod(ViewLead::class, 'content');
    expect($method->getDeclaringClass()->getName())->toBe(ViewRecord::class);
});

it('ViewLead source does not reference the removed Grid layout helpers', function () {
    $src = file_get_contents(
        (new ReflectionClass(ViewLead::class))->getFileName(),
    );

    expect($src)->not->toContain("Grid::make(['default' => 1, 'lg' => 2])");
    expect($src)->not->toContain('use Filament\Schemas\Components\Grid;');
});
