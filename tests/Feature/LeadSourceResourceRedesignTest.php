<?php

declare(strict_types=1);

use Filament\Actions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\CreateLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\EditLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\ListLeadSources;

it('uses the UsesExternalIdRouting trait', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(LeadSourceResource::class), true))
        ->toBeTrue();

    // The trait supplies both getRecordRouteKeyName() and the getUrl() override
    // that swaps the Model parameter for its external_id before delegating to
    // parent::getUrl(). Same fix as the parity series continuation and every
    // trait-adoption story since.
    expect(LeadSourceResource::getRecordRouteKeyName())->toBe('external_id');
});

it('inherits getRecordRouteKeyName + getUrl from the UsesExternalIdRouting trait, not from a local override', function (): void {
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(LeadSourceResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(LeadSourceResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(LeadSourceResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = LeadSourceResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(LeadSourceResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_lead_sources'));
});

it('recordActions register ViewAction then EditAction then DeleteAction with button()->hiddenLabel()', function (): void {
    $src = file_get_contents((new ReflectionClass(LeadSourceResource::class))->getFileName());

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

it('does not declare a local getRecordRouteKeyName method on LeadSourceResource', function (): void {
    // Regression guard for the AC's "remove the local getRecordRouteKeyName" contract.
    $src = file_get_contents((new ReflectionClass(LeadSourceResource::class))->getFileName());

    expect($src)->not->toContain('public static function getRecordRouteKeyName');
});

it('preserves table columns, defaultSort, and bulk delete action', function (): void {
    $src = file_get_contents((new ReflectionClass(LeadSourceResource::class))->getFileName());

    expect($src)->toContain("TextColumn::make('name')")
        ->and($src)->toContain("TextColumn::make('description')")
        ->and($src)->toContain("TextColumn::make('leads_count')")
        ->and($src)->toContain("->counts('leads')")
        ->and($src)->toContain("->defaultSort('name')")
        ->and($src)->toContain('Actions\\BulkActionGroup::make(')
        ->and($src)->toContain('Actions\\DeleteBulkAction::make()');
});

it('getPages() exposes index / create / view / edit', function (): void {
    $pages = LeadSourceResource::getPages();

    expect(array_keys($pages))->toBe(['index', 'create', 'view', 'edit']);

    $src = file_get_contents((new ReflectionClass(LeadSourceResource::class))->getFileName());

    expect($src)->toContain("'index' => " . class_basename(ListLeadSources::class) . "::route('/')")
        ->and($src)->toContain("'create' => " . class_basename(CreateLeadSource::class) . "::route('/create')")
        ->and($src)->toContain("'view' => ViewLeadSource::route('/{record}')")
        ->and($src)->toContain("'edit' => " . class_basename(EditLeadSource::class) . "::route('/{record}/edit')");
});

it('resource source references the AC-named actions.back_to_lead_sources translation key', function (): void {
    $src = file_get_contents((new ReflectionClass(LeadSourceResource::class))->getFileName());

    expect($src)->toContain('labels.actions.back_to_lead_sources');
});

it('en/fr/es label files declare actions.back_to_lead_sources with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_lead_sources'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_lead_sources'])->not->toBe('');
    }
});
