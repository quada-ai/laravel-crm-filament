<?php

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

function leadCustomFieldEntries(Lead $record, bool $grouped): array
{
    return LeadResource::crmCustomFieldEntries($record, $grouped);
}

function makeField(string $name, string $type, ?int $groupId = null): Field
{
    return Field::create([
        'external_id' => (string) Str::uuid(),
        'name' => $name,
        'key' => Str::slug($name, '_'),
        'type' => $type,
        'field_group_id' => $groupId,
    ]);
}

function makeFieldValue(Lead $lead, Field $field, ?string $value): FieldValue
{
    return FieldValue::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'field_valueable_type' => Lead::class,
        'field_valueable_id' => $lead->id,
        'value' => $value,
    ]);
}

it('crmCustomFieldEntries has the expected static signature on LeadResource', function () {
    $reflection = new ReflectionMethod(LeadResource::class, 'crmCustomFieldEntries');
    expect($reflection->isPublic())->toBeTrue();
    expect($reflection->isStatic())->toBeTrue();
    expect($reflection->getNumberOfRequiredParameters())->toBe(1);
    expect($reflection->getNumberOfParameters())->toBe(2);
    expect($reflection->getReturnType()?->getName())->toBe('array');
});

it('returns an empty array when the record has no matching FieldValues', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Empty lead',
    ]);

    expect(leadCustomFieldEntries($lead->fresh(), false))->toBe([]);
    expect(leadCustomFieldEntries($lead->fresh(), true))->toBe([]);
});

it('returns only ungrouped FieldValues when grouped is false', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead with mixed fields',
    ]);

    $group = FieldGroup::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'VIP',
        'model' => Lead::class,
    ]);

    $ungrouped = makeField('priority', 'text');
    $groupedField = makeField('vip_score', 'text', $group->id);

    makeFieldValue($lead, $ungrouped, 'high');
    makeFieldValue($lead, $groupedField, '9');

    $entries = leadCustomFieldEntries($lead->fresh(), false);

    expect($entries)->toHaveCount(1);
    expect($entries[0])->toBeInstanceOf(TextEntry::class);
    expect($entries[0]->getName())->toBe('custom.priority');
});

it('returns Sections keyed by FieldGroup->name when grouped is true', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Lead with groups',
    ]);

    $groupA = FieldGroup::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Marketing',
        'model' => Lead::class,
    ]);

    $groupB = FieldGroup::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Sales',
        'model' => Lead::class,
    ]);

    $ungrouped = makeField('priority', 'text');
    $marketingField = makeField('utm_source', 'text', $groupA->id);
    $salesField1 = makeField('quota_tier', 'text', $groupB->id);
    $salesField2 = makeField('owner_region', 'text', $groupB->id);

    makeFieldValue($lead, $ungrouped, 'high');
    makeFieldValue($lead, $marketingField, 'newsletter');
    makeFieldValue($lead, $salesField1, 'gold');
    makeFieldValue($lead, $salesField2, 'EMEA');

    $sections = leadCustomFieldEntries($lead->fresh(), true);

    expect($sections)->toHaveCount(2);
    foreach ($sections as $section) {
        expect($section)->toBeInstanceOf(Section::class);
    }

    // Inspect inner schema via Reflection — Section.childComponents holds them.
    $childRef = new ReflectionProperty(Section::class, 'childComponents');
    $childRef->setAccessible(true);

    $byHeading = [];
    foreach ($sections as $section) {
        $children = $childRef->getValue($section);
        $flat = $children['default'] ?? $children;
        $byHeading[$section->getHeading()] = array_map(fn ($c) => $c->getName(), $flat);
    }

    expect(array_keys($byHeading))->toContain('Marketing', 'Sales');
    expect($byHeading['Marketing'])->toBe(['custom.utm_source']);
    expect($byHeading['Sales'])->toBe(['custom.quota_tier', 'custom.owner_region']);
});

it('renders checkbox FieldValues as Yes/No', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Checkbox lead',
    ]);

    $field = makeField('subscribed', 'checkbox');
    makeFieldValue($lead, $field, '1');

    $unset = makeField('opted_in', 'checkbox');
    makeFieldValue($lead, $unset, '0');

    $entries = leadCustomFieldEntries($lead->fresh(), false);

    expect($entries)->toHaveCount(2);
    $states = array_map(fn ($e) => $e->getState(), $entries);
    expect($states)->toContain('Yes', 'No');
});

it('renders select FieldValues with the looked-up option label', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Select lead',
    ]);

    $field = makeField('region', 'select');
    $option = FieldOption::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'value' => 'apac',
        'label' => 'APAC',
    ]);

    makeFieldValue($lead, $field, (string) $option->id);

    $entries = leadCustomFieldEntries($lead->fresh(), false);
    expect($entries)->toHaveCount(1);
    expect($entries[0]->getState())->toBe('APAC');
});

it('renders radio FieldValues with the looked-up option label', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Radio lead',
    ]);

    $field = makeField('size', 'radio');
    $option = FieldOption::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'value' => 'lg',
        'label' => 'Large',
    ]);

    makeFieldValue($lead, $field, (string) $option->id);

    $entries = leadCustomFieldEntries($lead->fresh(), false);
    expect($entries[0]->getState())->toBe('Large');
});

it('renders checkbox_multiple FieldValues as a joined list of option labels', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Checkbox-multiple lead',
    ]);

    $field = makeField('tags', 'checkbox_multiple');
    $a = FieldOption::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'value' => 'a',
        'label' => 'Alpha',
    ]);
    $b = FieldOption::create([
        'external_id' => (string) Str::uuid(),
        'field_id' => $field->id,
        'value' => 'b',
        'label' => 'Bravo',
    ]);

    makeFieldValue($lead, $field, json_encode([$a->id, $b->id]));

    $entries = leadCustomFieldEntries($lead->fresh(), false);
    expect($entries[0]->getState())->toBe('Alpha, Bravo');
});

it('renders plain text FieldValues with their raw value', function () {
    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Text lead',
    ]);

    $field = makeField('notes', 'text');
    makeFieldValue($lead, $field, 'Big customer, follow up Monday');

    $entries = leadCustomFieldEntries($lead->fresh(), false);
    expect($entries[0]->getState())->toBe('Big customer, follow up Monday');
});
