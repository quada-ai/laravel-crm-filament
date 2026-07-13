<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\CreateFieldGroup;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\EditFieldGroup;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\ListFieldGroups;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\ViewFieldGroup;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(FieldGroupResource::class), true))
        ->toBeTrue();

    expect(FieldGroupResource::getRecordRouteKeyName())->toBe('external_id');
});

it('inherits getRecordRouteKeyName + getUrl from the trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(FieldGroupResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(FieldGroupResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(FieldGroupResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = FieldGroupResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(FieldGroupResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_field_groups'));
});

it('form drops the handle input so only the name TextInput remains editable', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldGroupResource::class))->getFileName());

    // Positive: form declares TextInput::make('name') with required + maxLength.
    expect($src)->toContain("TextInput::make('name')");
    expect($src)->toContain('->required()');
    expect($src)->toContain('->maxLength(255)');

    // Regression guard: handle must NOT appear as a form input on the resource.
    expect($src)->not->toContain("TextInput::make('handle')");
});

it('table columns render in the AC-named order name / system / fields_count', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldGroupResource::class))->getFileName());

    $namePos = strpos($src, "TextColumn::make('name')");
    $systemPos = strpos($src, "IconColumn::make('system')");
    $fieldsCountPos = strpos($src, "TextColumn::make('fields_count')");

    expect($namePos)->not->toBeFalse()
        ->and($systemPos)->not->toBeFalse()
        ->and($fieldsCountPos)->not->toBeFalse();

    expect($namePos)->toBeLessThan($systemPos)
        ->and($systemPos)->toBeLessThan($fieldsCountPos);

    // fields_count is wired via ->counts('fields') for the row-count aggregate.
    expect($src)->toContain("->counts('fields')");

    // system column is an IconColumn with ->boolean().
    $systemBlock = substr($src, $systemPos, 300);
    expect($systemBlock)->toContain('->boolean()');

    // handle column is dropped (was previously TextColumn::make('handle')).
    expect($src)->not->toContain("TextColumn::make('handle')");
});

it('defaults table sort to created_at desc', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldGroupResource::class))->getFileName());

    expect($src)->toContain("->defaultSort('created_at', 'desc')");
    expect($src)->not->toContain("->defaultSort('name')");
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel() and Delete requires confirmation', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldGroupResource::class))->getFileName());

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

it('getPages() exposes index / create / view / edit with the view page routed to /{record}', function (): void {
    $pages = FieldGroupResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(FieldGroupResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListFieldGroups::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateFieldGroup::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewFieldGroup::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditFieldGroup::class) . "::route('/{record}/edit')");
});

it('en/fr/es label files declare actions.back_to_field_groups with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_field_groups'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_field_groups'])->not->toBe('');
    }
});

it('ViewFieldGroup extends ViewRecord and binds to FieldGroupResource', function (): void {
    expect(is_subclass_of(ViewFieldGroup::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewFieldGroup::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(FieldGroupResource::class);
});
