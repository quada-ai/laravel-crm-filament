<?php

declare(strict_types=1);
use VentureDrake\LaravelCrmFilament\Concerns\ContactFieldsSchema;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('ships en/labels.php with the documented sections', function () {
    $en = require dirname(__DIR__, 2) . '/resources/lang/en/labels.php';

    expect($en)->toBeArray();
    foreach (['fields', 'contact', 'sales', 'money', 'campaign', 'chat', 'file', 'sections', 'actions', 'import', 'misc'] as $group) {
        expect($en)->toHaveKey($group);
    }
});

it('keeps fr/labels.php structurally identical to en/labels.php', function () {
    $en = require dirname(__DIR__, 2) . '/resources/lang/en/labels.php';
    $fr = require dirname(__DIR__, 2) . '/resources/lang/fr/labels.php';

    $flatten = function (array $a, string $prefix = '') use (&$flatten): array {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : "$prefix.$k";
            if (is_array($v)) {
                $out = array_merge($out, $flatten($v, $key));
            } else {
                $out[] = $key;
            }
        }
        sort($out);

        return $out;
    };

    expect($flatten($fr))->toEqual($flatten($en));
});

it('keeps es/labels.php structurally identical to en/labels.php', function () {
    $en = require dirname(__DIR__, 2) . '/resources/lang/en/labels.php';
    $es = require dirname(__DIR__, 2) . '/resources/lang/es/labels.php';

    $flatten = function (array $a, string $prefix = '') use (&$flatten): array {
        $out = [];
        foreach ($a as $k => $v) {
            $key = $prefix === '' ? (string) $k : "$prefix.$k";
            if (is_array($v)) {
                $out = array_merge($out, $flatten($v, $key));
            } else {
                $out[] = $key;
            }
        }
        sort($out);

        return $out;
    };

    expect($flatten($es))->toEqual($flatten($en));
});

it('routes the LeadResource form labels through the labels namespace', function () {
    $source = file_get_contents((new ReflectionClass(LeadResource::class))->getFileName());

    expect($source)->toContain("__('laravel-crm-filament::labels.sales.pipeline_stage')");
    expect($source)->toContain("__('laravel-crm-filament::labels.sales.lead_source')");
    expect($source)->toContain("__('laravel-crm-filament::labels.sales.lead_status')");
    expect($source)->toContain("__('laravel-crm-filament::labels.fields.owner')");
});

it('routes the ContactFieldsSchema labels through the labels namespace', function () {
    $source = file_get_contents((new ReflectionClass(ContactFieldsSchema::class))->getFileName());

    expect($source)->toContain("__('laravel-crm-filament::labels.contact.phone_numbers')");
    expect($source)->toContain("__('laravel-crm-filament::labels.contact.email_addresses')");
    expect($source)->toContain("__('laravel-crm-filament::labels.contact.addresses')");
});

it('routes Section::make headings through the labels namespace', function () {
    $source = file_get_contents((new ReflectionClass(HasCrmCustomFields::class))->getFileName());

    expect($source)->toContain("__('laravel-crm-filament::labels.sections.custom_fields')");
});

it('actually resolves __(laravel-crm-filament::labels.*) at runtime instead of returning the raw key', function () {
    // Regression guard: Spatie's Package::shortName() strips `laravel-`,
    // so $package->hasTranslations() alone registers the namespace as
    // `crm-filament::*` — every fully-qualified `laravel-crm-filament::*`
    // lookup would then fall back to the literal key string and render
    // as e.g. "laravel-crm-filament::labels.fields.id" in the host UI.
    expect(__('laravel-crm-filament::labels.fields.id'))
        ->toBe('ID');
    expect(__('laravel-crm-filament::labels.actions.list_view'))
        ->toBe('List view');
    expect(__('laravel-crm-filament::labels.actions.kanban_view'))
        ->toBe('Kanban view');
    expect(__('laravel-crm-filament::labels.fields.labels'))
        ->toBe('Labels');
    expect(__('laravel-crm-filament::labels.money.amount'))
        ->toBe('Amount');
});
