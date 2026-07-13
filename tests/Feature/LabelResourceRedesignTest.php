<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\CreateLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\EditLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ListLabels;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ViewLabel;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(LabelResource::class), true))
        ->toBeTrue();

    expect(LabelResource::getRecordRouteKeyName())->toBe('external_id');
});

it('inherits getRecordRouteKeyName + getUrl from the UsesExternalIdRouting trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(LabelResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(LabelResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(LabelResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = LabelResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(LabelResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_labels'));
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->button()->hiddenLabel()');
    $deletePos = strpos($src, 'Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($deletePos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos)
        ->and($editPos)->toBeLessThan($deletePos);
});

it('table defaultSort is set to created_at desc', function (): void {
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain("->defaultSort('created_at', 'desc')");
});

it('getPages() exposes index / create / view / edit (view added by US-005)', function (): void {
    $pages = LabelResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListLabels::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateLabel::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewLabel::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditLabel::class) . "::route('/{record}/edit')");
});

it('ViewLabel extends ViewRecord and binds to LabelResource', function (): void {
    expect(is_subclass_of(ViewLabel::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewLabel::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(LabelResource::class);
});

it('ViewLabel::content() root is a Grid(default=1, lg=2) with two Sections at columnSpan lg=1', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();
    $page->record = new Label(['name' => 'Priority']);

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

it('ViewLabel::getHeaderActions() returns three pills [backToIndex, Edit pencil, Delete trash]', function (): void {
    $page = (new ReflectionClass(ViewLabel::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewLabel::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Actions\Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(Actions\EditAction::class)
        ->and($actions[1]->getIcon())->toBe('heroicon-m-pencil-square')
        ->and($actions[2])->toBeInstanceOf(Actions\DeleteAction::class)
        ->and($actions[2]->getIcon())->toBe('heroicon-m-trash');
});

it('resource source references the AC-named actions.back_to_labels translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_labels');
});

it('en/fr/es label files declare actions.back_to_labels and sales.label with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_labels'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_labels'])->not->toBe('');

        expect($labels['sales']['label'] ?? null)->toBeString()
            ->and($labels['sales']['label'])->not->toBe('');
    }
});
