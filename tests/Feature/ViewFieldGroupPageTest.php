<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\ViewFieldGroup;

it('extends ViewRecord and binds to FieldGroupResource', function (): void {
    expect(is_subclass_of(ViewFieldGroup::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewFieldGroup::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(FieldGroupResource::class);
});

it('getHeaderActions() returns exactly [backToIndex, Edit with pencil, Delete with trash] as three pills', function (): void {
    $page = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewFieldGroup::class, 'getHeaderActions');
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
    $src = file_get_contents((new ReflectionClass(ViewFieldGroup::class))->getFileName());
    expect(substr_count($src, '->button()'))->toBeGreaterThanOrEqual(2);
    expect(substr_count($src, '->hiddenLabel()'))->toBeGreaterThanOrEqual(2);
});

it('content() root component is a Grid with default=1, lg=2 columns', function (): void {
    $page = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();
    $page->record = new FieldGroup(['name' => 'Contact Details']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $components = $result->getComponents(withHidden: true);

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Grid::class)
        ->and($components[0]->getColumns())->toBe(['default' => 1, 'lg' => 2]);
});

// LOCKS THE AC-MANDATED "avoids-double-panel-bug" CONTRACT:
// the right Grid child MUST be a Livewire instance directly,
// NOT a Section wrapper.
it('right Grid child is a Livewire instance and NOT a Section (avoids double-panel bug)', function (): void {
    $page = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();
    $page->record = new FieldGroup(['name' => 'Contact Details']);

    $schema = Schema::make($page);
    $result = $page->content($schema);
    $grid = $result->getComponents(withHidden: true)[0];

    $ref = new ReflectionProperty(Grid::class, 'childComponents');
    $ref->setAccessible(true);
    $children = $ref->getValue($grid);
    $childList = array_values($children['default'] ?? $children);

    expect($childList)->toHaveCount(2)
        ->and($childList[0])->toBeInstanceOf(Section::class)
        ->and($childList[0]->getColumnSpan())->toBe(['lg' => 1])
        // AC-critical: right child MUST be a direct Livewire embed, NOT a Section.
        ->and($childList[1])->toBeInstanceOf(Livewire::class)
        ->and($childList[1])->not->toBeInstanceOf(Section::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('left Details Section carries a system badge TextEntry + a handle TextEntry', function (): void {
    $page = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();
    $page->record = new FieldGroup([
        'name' => 'Contact Details',
        'system' => true,
        'handle' => 'contact_details',
    ]);

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

    expect($secChildList)->toHaveCount(2);
    $names = array_map(fn ($e) => $e->getName(), $secChildList);
    expect($names)->toBe(['system', 'handle']);

    foreach ($secChildList as $entry) {
        expect($entry)->toBeInstanceOf(TextEntry::class);
    }

    // system entry is a badge; source-grep for ->badge() scoped to the entry block.
    $src = file_get_contents((new ReflectionClass(ViewFieldGroup::class))->getFileName());
    $systemPos = strpos($src, "TextEntry::make('system')");
    expect($systemPos)->not->toBeFalse();
    $systemBlock = substr($src, $systemPos, 400);
    expect($systemBlock)->toContain('->badge()');
});

it('system TextEntry state resolves to Yes for a truthy record and No for a falsy record', function (): void {
    // TRUE case.
    $pageTrue = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();
    $pageTrue->record = new FieldGroup(['name' => 'X', 'system' => true]);
    $schemaTrue = Schema::make($pageTrue);
    $resultTrue = $pageTrue->content($schemaTrue);

    $refT = new ReflectionProperty(Grid::class, 'childComponents');
    $refT->setAccessible(true);
    $childrenT = $refT->getValue($resultTrue->getComponents(withHidden: true)[0]);
    $childListT = array_values($childrenT['default'] ?? $childrenT);
    $leftT = $childListT[0];

    $secRefT = new ReflectionProperty(Section::class, 'childComponents');
    $secRefT->setAccessible(true);
    $secChildrenT = $secRefT->getValue($leftT);
    $secListT = array_values($secChildrenT['default'] ?? $secChildrenT);
    expect($secListT[0]->getState())->toBe(__('laravel-crm::lang.yes'));

    // FALSE case.
    $pageFalse = (new ReflectionClass(ViewFieldGroup::class))->newInstanceWithoutConstructor();
    $pageFalse->record = new FieldGroup(['name' => 'X', 'system' => false]);
    $schemaFalse = Schema::make($pageFalse);
    $resultFalse = $pageFalse->content($schemaFalse);

    $childrenF = $refT->getValue($resultFalse->getComponents(withHidden: true)[0]);
    $childListF = array_values($childrenF['default'] ?? $childrenF);
    $leftF = $childListF[0];
    $secChildrenF = $secRefT->getValue($leftF);
    $secListF = array_values($secChildrenF['default'] ?? $secChildrenF);
    expect($secListF[0]->getState())->toBe(__('laravel-crm::lang.no'));
});

it('right Livewire embed keys on the record primary key and points at FieldGroupFieldsRelationManager', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewFieldGroup::class))->getFileName());

    // Structural source-grep: the Livewire embed, ownerData shape, and key.
    expect($src)->toContain('Livewire::make(FieldGroupFieldsRelationManager::class');
    expect($src)->toContain("'ownerRecord' => \$record");
    expect($src)->toContain("'pageClass' => static::class");
    expect($src)->toContain("->key('field-group-fields-' . \$record->getKey())");
});

it('references the AC-named translation keys and follows the eager $record capture pattern', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewFieldGroup::class))->getFileName());

    expect($src)->toContain('labels.sections.details')
        ->and($src)->toContain('labels.fields.system')
        ->and($src)->toContain('labels.fields.handle')
        ->and($src)->toContain('$record = $this->record;');
});
