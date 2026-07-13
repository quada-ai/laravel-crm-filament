<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\ViewField;

it('extends ViewRecord and binds to FieldResource', function (): void {
    expect(is_subclass_of(ViewField::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewField::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(FieldResource::class);
});

it('getHeaderActions() returns exactly [backToIndex, Edit with pencil, Delete with trash] as three pills', function (): void {
    $page = (new ReflectionClass(ViewField::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewField::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(EditAction::class)
        ->and($actions[1]->getIcon())->toBe('heroicon-m-pencil-square')
        ->and($actions[2])->toBeInstanceOf(DeleteAction::class)
        ->and($actions[2]->getIcon())->toBe('heroicon-m-trash');

    // Source-grep regression guard: Edit + Delete pills use button()->hiddenLabel().
    $src = file_get_contents((new ReflectionClass(ViewField::class))->getFileName());
    expect(substr_count($src, '->button()'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($src, '->hiddenLabel()'))->toBeGreaterThanOrEqual(2);
});

it('content() root is a Grid with default=1, lg=2 columns and two Section children each columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewField::class))->newInstanceWithoutConstructor();
    $page->record = new Field(['name' => 'Priority', 'type' => 'text']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $components = $result->getComponents(withHidden: true);

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Grid::class)
        ->and($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($components[0]);
    $childList = array_values($children['default'] ?? $children);

    expect($childList)->toHaveCount(2)
        ->and($childList[0])->toBeInstanceOf(Section::class)
        ->and($childList[0]->getColumnSpan())->toBe(['lg' => 1])
        ->and($childList[1])->toBeInstanceOf(Section::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('left Details Section carries type / group / required / default / system / handle TextEntries (plus optional options)', function (): void {
    $page = (new ReflectionClass(ViewField::class))->newInstanceWithoutConstructor();
    $page->record = new Field(['name' => 'Priority', 'type' => 'text']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);
    $leftSection = $childList[0];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($leftSection);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    // All entries must be TextEntry instances.
    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }

    // Collect entry names — at minimum, the AC-mandated 7 must be present.
    // The 'options' entry is conditional: for type=text (non-choice) it renders
    // but is hidden via ->visible(fn () => $optionsState !== null) — Filament
    // includes it in getComponents(withHidden: true).
    $names = array_map(fn ($e) => $e->getName(), $secChildList);
    expect($names)->toContain('type', 'field_group', 'required', 'default', 'system', 'handle');
});

it('right Attached To Section carries a single badge TextEntry named attached_to', function (): void {
    $page = (new ReflectionClass(ViewField::class))->newInstanceWithoutConstructor();
    $page->record = new Field(['name' => 'Priority', 'type' => 'text']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);
    $rightSection = $childList[1];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = $secRef->getValue($rightSection);
    $secChildList = array_values($secChildren['default'] ?? $secChildren);

    expect($secChildList)->toHaveCount(1)
        ->and($secChildList[0])->toBeInstanceOf(TextEntry::class)
        ->and($secChildList[0]->getName())->toBe('attached_to');

    // Source-grep the ->badge() modifier scoped to the attached_to entry.
    $src = file_get_contents((new ReflectionClass(ViewField::class))->getFileName());
    $attachedPos = strpos($src, "TextEntry::make('attached_to')");
    expect($attachedPos)->not->toBeFalse();
    $attachedBlock = substr($src, $attachedPos, 500);
    expect($attachedBlock)->toContain('->badge()');
});

it('attached_to state returns pluralised class basenames of FieldModel associations', function (): void {
    // Seed a Field + attach 2 FieldModel rows (Lead + Person).
    $field = Field::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Priority',
        'type' => 'text',
        'key' => 'priority',
    ]);
    FieldModel::create(['field_id' => $field->id, 'model' => Lead::class]);
    FieldModel::create(['field_id' => $field->id, 'model' => Person::class]);

    $page = (new ReflectionClass(ViewField::class))->newInstanceWithoutConstructor();
    $page->record = $field->fresh();

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $gridChildren = array_values(($ref->getValue($grid))['default'] ?? $ref->getValue($grid));
    $rightSection = $gridChildren[1];

    $secRef = new ReflectionProperty(Section::class, 'childComponents');
    $secRef->setAccessible(true);
    $secChildren = array_values(($secRef->getValue($rightSection))['default'] ?? $secRef->getValue($rightSection));

    $attachedEntry = $secChildren[0];

    // Read via the public getState() API (canonical for ->state($value) plain-value setter).
    $state = $attachedEntry->getState();
    expect($state)->toBeArray()
        ->and($state)->toContain('Leads', 'People');
});

it('references the AC-named translation keys (sections.details, fields.attached_to)', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewField::class))->getFileName());

    expect($src)->toContain('labels.sections.details')
        ->and($src)->toContain('labels.fields.attached_to');
});

it('follows the eager $record capture pattern from commit 54b6a24', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewField::class))->getFileName());

    expect($src)->toContain('$record = $this->record;');
});
