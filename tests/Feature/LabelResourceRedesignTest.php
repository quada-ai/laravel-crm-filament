<?php

declare(strict_types=1);

use Filament\Actions\Action;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\CreateLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\EditLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ListLabels;

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
    expect($action)->toBeInstanceOf(Action::class)
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

it('does not declare a local getRecordRouteKeyName method on LabelResource', function (): void {
    // Regression guard for the AC's "remove the local getRecordRouteKeyName" contract.
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->not->toContain('public static function getRecordRouteKeyName');
});

it('preserves form components, table columns, defaultSort, and bulk delete action', function (): void {
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain("TextInput::make('name')")
        ->and($src)->toContain("ColorPicker::make('hex')")
        ->and($src)->toContain("Textarea::make('description')")
        ->and($src)->toContain("TextColumn::make('name')")
        ->and($src)->toContain("ColorColumn::make('hex')")
        ->and($src)->toContain("->defaultSort('name')")
        ->and($src)->toContain('Actions\\BulkActionGroup::make(')
        ->and($src)->toContain('Actions\\DeleteBulkAction::make()');
});

it('getPages() exposes index / create / view / edit (view added by US-005)', function (): void {
    $pages = LabelResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListLabels::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateLabel::class) . "::route('/create')")
        ->and($src)->toContain("'view' => ViewLabel::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditLabel::class) . "::route('/{record}/edit')");
});

it('resource source references the AC-named actions.back_to_labels translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(LabelResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_labels');
});

it('en/fr/es label files declare actions.back_to_labels with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_labels'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_labels'])->not->toBe('');
    }
});

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

it('persists a Label with a UUID external_id when the mutate hook is applied', function (): void {
    $method = new ReflectionMethod(CreateLabel::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    $page = (new ReflectionClass(CreateLabel::class))->newInstanceWithoutConstructor();
    $mutated = $method->invoke($page, ['name' => 'Priority', 'hex' => 'ff0000']);

    $label = Label::create($mutated);

    expect($label->external_id)->not->toBeNull();
    expect($label->external_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    expect($label->name)->toBe('Priority');
});
