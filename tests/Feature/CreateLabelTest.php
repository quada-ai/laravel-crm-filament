<?php

declare(strict_types=1);

use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\CreateLabel;

it('declares mutateFormDataBeforeCreate locally on CreateLabel', function (): void {
    $method = new ReflectionMethod(CreateLabel::class, 'mutateFormDataBeforeCreate');

    expect($method->getDeclaringClass()->getName())->toBe(CreateLabel::class);
    expect($method->isProtected())->toBeTrue();
    expect($method->getNumberOfRequiredParameters())->toBe(1);
    expect($method->getReturnType()?->getName())->toBe('array');
});

it('stamps a UUID external_id on empty form data', function (): void {
    $method = new ReflectionMethod(CreateLabel::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateLabel::class))->newInstanceWithoutConstructor();
    $result = $method->invoke($page, ['name' => 'Priority', 'hex' => '#ff0000']);

    expect($result)->toHaveKey('external_id');
    expect($result['external_id'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    expect($result['name'])->toBe('Priority');
    expect($result['hex'])->toBe('#ff0000');
});

it('preserves an existing external_id when the form data already carries one (idempotency)', function (): void {
    $method = new ReflectionMethod(CreateLabel::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateLabel::class))->newInstanceWithoutConstructor();
    $existing = '12345678-1234-1234-1234-123456789012';
    $result = $method->invoke($page, ['name' => 'Priority', 'external_id' => $existing]);

    expect($result['external_id'])->toBe($existing);
});

it('persists a Label with a non-null UUID external_id (positive-presence assertion — TestSchema masks the NOT NULL constraint failure)', function (): void {
    // The plugin's TestSchema declares crm_labels.external_id as nullable
    // (unlike core's production migration which is NOT NULL). Without the
    // positive-presence check, this test would silently pass on a
    // pre-mutate-hook regression: Label::create() would succeed with
    // external_id=null, and no schema-level constraint would fire. Asserting
    // ->not->toBeNull() catches the exact bug the mutate hook exists to
    // prevent — panel-driven Label creates producing rows that 404 on the
    // show/edit routes (which key on external_id).
    $method = new ReflectionMethod(CreateLabel::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateLabel::class))->newInstanceWithoutConstructor();
    $mutated = $method->invoke($page, ['name' => 'Priority', 'hex' => 'ff0000']);

    $label = Label::create($mutated);

    expect($label->external_id)->not->toBeNull();
    expect($label->external_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    expect($label->name)->toBe('Priority');
});
