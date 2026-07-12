<?php

use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\CreateProductCategory;

it('declares mutateFormDataBeforeCreate locally on CreateProductCategory', function () {
    $method = new ReflectionMethod(CreateProductCategory::class, 'mutateFormDataBeforeCreate');

    expect($method->getDeclaringClass()->getName())->toBe(CreateProductCategory::class);
    expect($method->isProtected())->toBeTrue();
    expect($method->getNumberOfRequiredParameters())->toBe(1);
    expect($method->getReturnType()?->getName())->toBe('array');
});

it('stamps a UUID external_id on empty form data', function () {
    $method = new ReflectionMethod(CreateProductCategory::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateProductCategory::class))->newInstanceWithoutConstructor();
    $result = $method->invoke($page, ['name' => 'Widgets']);

    expect($result)->toHaveKey('external_id');
    expect($result['external_id'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    expect($result['name'])->toBe('Widgets');
});

it('preserves an existing external_id when the form data already carries one', function () {
    $method = new ReflectionMethod(CreateProductCategory::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateProductCategory::class))->newInstanceWithoutConstructor();
    $existing = '12345678-1234-1234-1234-123456789012';
    $result = $method->invoke($page, ['name' => 'Widgets', 'external_id' => $existing]);

    expect($result['external_id'])->toBe($existing);
});

it('persists a ProductCategory with a UUID external_id when the mutate hook is applied', function () {
    $method = new ReflectionMethod(CreateProductCategory::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateProductCategory::class))->newInstanceWithoutConstructor();
    $mutated = $method->invoke($page, ['name' => 'Widgets']);

    $category = ProductCategory::create($mutated);

    expect($category->external_id)->not->toBeNull();
    expect($category->external_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    expect($category->name)->toBe('Widgets');
});
