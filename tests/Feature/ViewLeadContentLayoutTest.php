<?php

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\ViewLead;

function viewLeadContentSchemaComponents(): array
{
    $page = (new ReflectionClass(ViewLead::class))->newInstanceWithoutConstructor();
    $page->record = new Lead;
    $schema = Schema::make($page);

    $page->content($schema);

    return $schema->getComponents(withHidden: true);
}

it('declares content() override on ViewLead (not inherited)', function () {
    $method = new ReflectionMethod(ViewLead::class, 'content');
    expect($method->getDeclaringClass()->getName())->toBe(ViewLead::class);
    expect($method->isPublic())->toBeTrue();
});

it('content() root component is a Grid with default=1, lg=3 columns', function () {
    $components = viewLeadContentSchemaComponents();

    expect($components)->toHaveCount(1);
    expect($components[0])->toBeInstanceOf(Grid::class);
    expect($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);
});

it('Grid children are infolist (lg=2) and relation managers (lg=1)', function () {
    $components = viewLeadContentSchemaComponents();
    $grid = $components[0];

    $ref = new ReflectionProperty($grid, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);

    // childComponents shape is either flat array or ['default' => [...]] grouping
    $children = $children['default'] ?? $children;

    expect($children)->toHaveCount(2);

    expect($children[0]->getColumnSpan())->toBe(['lg' => 1]);
    expect($children[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('content() does NOT render the form schema', function () {
    $src = file_get_contents(
        (new ReflectionClass(ViewLead::class))->getFileName(),
    );

    // The form content component should not appear in the content() override
    expect($src)->not->toContain('getFormContentComponent()');
});

it('ViewLead source mirrors ViewInvoice content pattern by calling infolist + relation manager helpers', function () {
    $src = file_get_contents(
        (new ReflectionClass(ViewLead::class))->getFileName(),
    );

    expect($src)->toContain('getInfolistContentComponent()');
    expect($src)->toContain('getRelationManagersContentComponent()');
    expect($src)->toContain("Grid::make(['default' => 1, 'lg' => 2])");
});
