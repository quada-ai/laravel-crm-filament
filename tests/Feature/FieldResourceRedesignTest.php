<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\CreateField;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\EditField;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\ListFields;
use VentureDrake\LaravelCrmFilament\Resources\Fields\Pages\ViewField;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(FieldResource::class), true))
        ->toBeTrue();

    expect(FieldResource::getRecordRouteKeyName())->toBe('external_id');
});

it('inherits getRecordRouteKeyName + getUrl from the trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(FieldResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(FieldResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(FieldResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = FieldResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(FieldResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_fields'));
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

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

it('form components render in the AC-named order name -> type -> field_group_id -> default -> required -> options -> models', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    // Positional strpos walk locks the exact ordering the AC specifies.
    $namePos = strpos($src, "TextInput::make('name')");
    $typePos = strpos($src, "Select::make('type')");
    $groupPos = strpos($src, "Select::make('field_group_id')");
    $defaultPos = strpos($src, "TextInput::make('default')");
    $requiredPos = strpos($src, "Toggle::make('required')");
    $optionsPos = strpos($src, "Repeater::make('options')");
    $modelsPos = strpos($src, "Select::make('models')");

    expect($namePos)->not->toBeFalse()
        ->and($typePos)->not->toBeFalse()
        ->and($groupPos)->not->toBeFalse()
        ->and($defaultPos)->not->toBeFalse()
        ->and($requiredPos)->not->toBeFalse()
        ->and($optionsPos)->not->toBeFalse()
        ->and($modelsPos)->not->toBeFalse();

    expect($namePos)->toBeLessThan($typePos)
        ->and($typePos)->toBeLessThan($groupPos)
        ->and($groupPos)->toBeLessThan($defaultPos)
        ->and($defaultPos)->toBeLessThan($requiredPos)
        ->and($requiredPos)->toBeLessThan($optionsPos)
        ->and($optionsPos)->toBeLessThan($modelsPos);
});

it('form does not render a handle input (observer preserves it silently)', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    // Regression guard: handle must NOT appear as a form input on the resource.
    expect($src)->not->toContain("TextInput::make('handle')");
});

it('table columns render in the AC-named order type / fieldGroup.name / name / required / default / system / attached_to', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    $typePos = strpos($src, "TextColumn::make('type')");
    $groupPos = strpos($src, "TextColumn::make('fieldGroup.name')");
    $namePos = strpos($src, "TextColumn::make('name')");
    $requiredPos = strpos($src, "IconColumn::make('required')");
    $defaultPos = strpos($src, "TextColumn::make('default')");
    $systemPos = strpos($src, "IconColumn::make('system')");
    $attachedPos = strpos($src, "TextColumn::make('attached_to')");

    expect($typePos)->not->toBeFalse()
        ->and($groupPos)->not->toBeFalse()
        ->and($namePos)->not->toBeFalse()
        ->and($requiredPos)->not->toBeFalse()
        ->and($defaultPos)->not->toBeFalse()
        ->and($systemPos)->not->toBeFalse()
        ->and($attachedPos)->not->toBeFalse();

    expect($typePos)->toBeLessThan($groupPos)
        ->and($groupPos)->toBeLessThan($namePos)
        ->and($namePos)->toBeLessThan($requiredPos)
        ->and($requiredPos)->toBeLessThan($defaultPos)
        ->and($defaultPos)->toBeLessThan($systemPos)
        ->and($systemPos)->toBeLessThan($attachedPos);
});

it('table eager-loads fieldModels via modifyQueryUsing and defaults sort to created_at desc', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    expect($src)->toContain("->modifyQueryUsing(fn (\$query) => \$query->with('fieldModels'))")
        ->and($src)->toContain("->defaultSort('created_at', 'desc')");
});

it('attached_to column is a computed badge with gray color and a pluralised class-basename state closure', function (): void {
    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    // Locate the attached_to column block and verify the AC-named modifier chain.
    $attachedPos = strpos($src, "TextColumn::make('attached_to')");
    expect($attachedPos)->not->toBeFalse();

    // Extract the ~500 chars after the attached_to column start to scope the assertion.
    $attachedBlock = substr($src, $attachedPos, 600);

    expect($attachedBlock)->toContain('->badge()')
        ->and($attachedBlock)->toContain("->color('gray')")
        ->and($attachedBlock)->toContain('$record->fieldModels')
        ->and($attachedBlock)->toContain('Str::plural(class_basename($fm->model))');
});

it('getPages() exposes index / create / view / edit with the view page routed to /{record}', function (): void {
    $pages = FieldResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(FieldResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListFields::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateField::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewField::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditField::class) . "::route('/{record}/edit')");
});

it('en/fr/es label files declare actions.back_to_fields with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_fields'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_fields'])->not->toBe('');
    }
});

it('ViewField extends ViewRecord and binds to FieldResource', function (): void {
    expect(is_subclass_of(ViewField::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewField::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(FieldResource::class);
});
