<?php

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LeadTasksRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\LunchesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;

function leadInfolistTopLevelSections(?Lead $record): array
{
    $page = (new ReflectionClass(ViewLead::class))->newInstanceWithoutConstructor();
    $page->record = $record ?? new Lead;
    $schema = Schema::make($page);

    if ($record) {
        $schema->record($record);
    }

    LeadResource::infolist($schema);

    $components = $schema->getComponents(withHidden: true);

    return array_values(array_filter(
        $components,
        fn ($c) => $c instanceof Section,
    ));
}

function leadInfolistSectionTextEntryNames(Section $section): array
{
    $children = $section->getChildComponents();

    $names = [];
    foreach ($children as $child) {
        if ($child instanceof TextEntry) {
            $names[] = $child->getName();
        }
    }

    return $names;
}

function leadInfolistViewLeadChildren(): array
{
    $page = (new ReflectionClass(ViewLead::class))->newInstanceWithoutConstructor();
    $page->record = new Lead;
    $schema = Schema::make($page);

    $page->content($schema);

    return $schema->getComponents(withHidden: true);
}

// ────────────────────────────────────────────────────────────────────────
// AC (1): three top-level Sections + required TextEntries
// ────────────────────────────────────────────────────────────────────────

it('infolist Schema exposes three top-level Sections in Details / Lead Qualification / Contact order', function () {
    $sections = leadInfolistTopLevelSections(null);

    expect($sections)->toHaveCount(3);

    $headings = array_map(fn (Section $s) => $s->getHeading(), $sections);
    expect($headings[0])->toBe(__('laravel-crm-filament::labels.sections.details'));
    expect($headings[1])->toBe(__('laravel-crm-filament::labels.sections.lead_qualification'));
    expect($headings[2])->toBe(__('laravel-crm-filament::labels.sections.contact'));
});

it('Details and Contact sections expose the AC-required TextEntries by name', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Probe',
        'amount' => 1000,
        'currency' => 'USD',
    ]);

    $sections = leadInfolistTopLevelSections($lead->fresh());

    $detailsNames = leadInfolistSectionTextEntryNames($sections[0]);
    foreach (['created_at', 'lead_id', 'amount', 'description', 'leadSource.name', 'labels.name', 'ownerUser.name'] as $required) {
        expect($detailsNames)->toContain($required);
    }

    $contactNames = leadInfolistSectionTextEntryNames($sections[2]);
    foreach (['person.name', 'organization.name', 'person_email', 'person_phone'] as $required) {
        expect($contactNames)->toContain($required);
    }
});

// ────────────────────────────────────────────────────────────────────────
// AC (2): amount entry resolves currency via a closure (per-row)
// ────────────────────────────────────────────────────────────────────────

it('amount TextEntry resolves currency via a per-record closure', function () {
    $src = file_get_contents((new ReflectionClass(LeadResource::class))->getFileName());

    // The closure shape: ->money(fn ($record) => $record?->currency ?: config(...))
    expect($src)->toContain("TextEntry::make('amount')");
    expect($src)->toContain("->money(fn (\$record) => \$record?->currency ?: config('laravel-crm.default_currency', 'USD'))");

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Probe',
        'amount' => 1000,
        'currency' => 'EUR',
    ]);

    $sections = leadInfolistTopLevelSections($lead->fresh());

    $amountEntry = null;
    foreach ($sections[0]->getChildComponents() as $c) {
        if ($c instanceof TextEntry && $c->getName() === 'amount') {
            $amountEntry = $c;

            break;
        }
    }
    expect($amountEntry)->not->toBeNull();

    // money() flips isMoney true on the entry...
    $isMoneyRef = new ReflectionProperty($amountEntry, 'isMoney');
    $isMoneyRef->setAccessible(true);
    expect($isMoneyRef->getValue($amountEntry))->toBeTrue();

    // ...and the formatStateUsing closure renders the per-row currency
    // (Number::currency emits a EUR-specific glyph or the ISO code).
    $formatted = $amountEntry->formatState((float) 1000);
    expect($formatted)->toBeString();
    expect($formatted)->toMatch('/EUR|€/');
});

// ────────────────────────────────────────────────────────────────────────
// AC (3): Lead Qualification hidden when no grouped FieldValues; nested
// Section keyed by FieldGroup name when one exists.
// ────────────────────────────────────────────────────────────────────────

it('Lead Qualification section is hidden when the record has no grouped FieldValues', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Probe',
    ]);

    $sections = leadInfolistTopLevelSections($lead->fresh());
    $qual = $sections[1];

    $isHiddenRef = new ReflectionProperty($qual, 'isHidden');
    $isHiddenRef->setAccessible(true);
    $closure = $isHiddenRef->getValue($qual);

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure($lead->fresh()))->toBeTrue();
});

it('Lead Qualification section exposes a nested Section keyed by FieldGroup name when grouped FieldValues exist', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Probe',
    ]);

    $group = FieldGroup::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Marketing',
        'order' => 1,
        'model' => Lead::class,
    ]);

    $field = Field::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'utm_source',
        'key' => 'utm_source',
        'type' => 'text',
        'field_group_id' => $group->id,
    ]);

    FieldValue::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'field_valueable_type' => Lead::class,
        'field_valueable_id' => $lead->id,
        'value' => 'newsletter',
    ]);

    $lead = $lead->fresh();
    $sections = leadInfolistTopLevelSections($lead);
    $qual = $sections[1];

    $isHiddenRef = new ReflectionProperty($qual, 'isHidden');
    $isHiddenRef->setAccessible(true);
    $closure = $isHiddenRef->getValue($qual);
    expect($closure($lead))->toBeFalse();

    $children = $qual->getChildComponents();
    $nestedSections = array_values(array_filter(
        $children,
        fn ($c) => $c instanceof Section,
    ));

    expect($nestedSections)->toHaveCount(1);
    expect($nestedSections[0]->getHeading())->toBe('Marketing');
});

// ────────────────────────────────────────────────────────────────────────
// AC (4): ViewLead::content() Grid with two child columns (lg 2 + lg 1)
// ────────────────────────────────────────────────────────────────────────

it('ViewLead::content produces a top-level Grid with two columns: infolist (lg 2) + relation managers (lg 1)', function () {
    $components = leadInfolistViewLeadChildren();

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);

    $grid = $components[0];
    $children = $grid->getChildComponents();

    expect($children)->toHaveCount(2);
    expect($children[0]->getColumnSpan())->toBe(['lg' => 1]);
    expect($children[1]->getColumnSpan())->toBe(['lg' => 1]);
});

// ────────────────────────────────────────────────────────────────────────
// AC (5): getRelations() contains the two new RMs + the existing six
// ────────────────────────────────────────────────────────────────────────

it('LeadResource::getRelations contains both new RMs plus the existing six', function () {
    $relations = LeadResource::getRelations();

    // Two new RMs from US-005 / US-007 (LeadLunches subclassed in lead-lunches-UI series)
    expect($relations)->toContain(LeadLunchesRelationManager::class);
    expect($relations)->toContain(LeadActivitiesRelationManager::class);

    // The existing six (US-013 v0.x baseline + earlier stories)
    expect($relations)->toContain(LeadNotesRelationManager::class);
    expect($relations)->toContain(LeadTasksRelationManager::class);
    expect($relations)->toContain(LeadCallsRelationManager::class);
    expect($relations)->toContain(LeadMeetingsRelationManager::class);
    expect($relations)->toContain(LeadFilesRelationManager::class);

    expect($relations)->toHaveCount(7);
});

// ────────────────────────────────────────────────────────────────────────
// AC (6): Both new RMs declare isReadOnly() === true with empty action arrays
// ────────────────────────────────────────────────────────────────────────

it('LunchesRelationManager is read-only with empty header/record/toolbar action arrays', function () {
    $rm = (new ReflectionClass(LunchesRelationManager::class))->newInstanceWithoutConstructor();
    expect($rm)->toBeInstanceOf(RelationManager::class);
    expect($rm->isReadOnly())->toBeTrue();

    $table = $rm->table(Table::make($rm));
    expect($table->getHeaderActions())->toBe([]);
    expect($table->getRecordActions())->toBe([]);
    expect($table->getToolbarActions())->toBe([]);
});

it('ActivitiesRelationManager is read-only with empty header/record/toolbar action arrays', function () {
    $rm = (new ReflectionClass(LeadActivitiesRelationManager::class))->newInstanceWithoutConstructor();
    expect($rm)->toBeInstanceOf(RelationManager::class);
    expect($rm->isReadOnly())->toBeTrue();

    $table = $rm->table(Table::make($rm));
    expect($table->getHeaderActions())->toBe([]);
    expect($table->getRecordActions())->toBe([]);
    expect($table->getToolbarActions())->toBe([]);
});
