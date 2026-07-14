<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\CreateChatWidget;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\EditChatWidget;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\ListChatWidgets;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages\ViewChatWidget;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(ChatWidgetResource::class), true))
        ->toBeTrue();

    expect(ChatWidgetResource::getRecordRouteKeyName())->toBe('external_id');
});

it('inherits getRecordRouteKeyName + getUrl from the UsesExternalIdRouting trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(ChatWidgetResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(ChatWidgetResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(ChatWidgetResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = ChatWidgetResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(ChatWidgetResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_chat_widgets'));
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(ChatWidgetResource::class))->getFileName());

    expect($src)->toContain('Actions\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\EditAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');

    $viewPos = strpos($src, 'Actions\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\EditAction::make()->button()->hiddenLabel()');
    $deletePos = strpos($src, 'Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');
    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($deletePos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos)
        ->and($editPos)->toBeLessThan($deletePos);
});

it('getPages() exposes index / create / view / edit', function (): void {
    $pages = ChatWidgetResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(ChatWidgetResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListChatWidgets::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateChatWidget::class) . "::route('/create')")
        ->and($src)->toContain("'view' => " . class_basename(ViewChatWidget::class) . "::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditChatWidget::class) . "::route('/{record}/edit')");
});

it('ViewChatWidget extends ViewRecord and binds to ChatWidgetResource', function (): void {
    expect(is_subclass_of(ViewChatWidget::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewChatWidget::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(ChatWidgetResource::class);
});

it('ViewChatWidget preserves the custom embed view path plus getEmbedSnippet/getIframeUrl helpers', function (): void {
    $src = file_get_contents((new ReflectionClass(ViewChatWidget::class))->getFileName());

    expect($src)->toContain("protected string \$view = 'laravel-crm-filament::chat-widgets.embed'")
        ->and($src)->toContain('public function getEmbedSnippet(): string')
        ->and($src)->toContain('public function getIframeUrl(): string')
        ->and($src)->toContain("route('laravel-crm.portal.chat.embed'")
        ->and($src)->toContain("route('laravel-crm.portal.chat.widget'");
});

it('ViewChatWidget::getHeaderActions() returns three pills [backToIndex, Edit pencil, Delete trash]', function (): void {
    $page = (new ReflectionClass(ViewChatWidget::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewChatWidget::class, 'getHeaderActions');
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

it('resource source references the AC-named actions.back_to_chat_widgets translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(ChatWidgetResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_chat_widgets');
});

it('en/fr/es label files declare actions.back_to_chat_widgets with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_chat_widgets'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_chat_widgets'])->not->toBe('');
    }
});
