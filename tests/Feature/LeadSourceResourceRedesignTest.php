<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\CreateLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\EditLeadSource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\ListLeadSources;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\Pages\ViewLeadSource;

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
    expect($action)->toBeInstanceOf(Action::class)
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

it('ViewLeadSource extends ViewRecord and binds to LeadSourceResource', function (): void {
    expect(is_subclass_of(ViewLeadSource::class, ViewRecord::class))->toBeTrue();

    $ref = new ReflectionClass(ViewLeadSource::class);
    $resourceProp = $ref->getProperty('resource');
    expect($resourceProp->getDefaultValue())->toBe(LeadSourceResource::class);
});

it('ViewLeadSource::content() root is a Grid(default=1, lg=2) with a Section on the left and a direct Livewire embed on the right', function (): void {
    $page = (new ReflectionClass(ViewLeadSource::class))->newInstanceWithoutConstructor();
    $page->record = new LeadSource(['name' => 'Referral']);

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
        // AC: right column is a direct Livewire child (NOT wrapped in a Section — avoids double-panel bug)
        ->and($childList[1])->toBeInstanceOf(Livewire::class)
        ->and($childList[1])->not->toBeInstanceOf(Section::class)
        ->and($childList[1]->getColumnSpan())->toBe(['lg' => 1]);
});

it('ViewLeadSource::getHeaderActions() returns three pills [backToIndex, Edit with pencil, Delete with trash] with correct icons', function (): void {
    $page = (new ReflectionClass(ViewLeadSource::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewLeadSource::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3);
    expect($actions[0])->toBeInstanceOf(Action::class);
    expect($actions[0]->getName())->toBe('backToIndex');
    expect($actions[1])->toBeInstanceOf(EditAction::class);
    expect($actions[1]->getIcon())->toBe('heroicon-m-pencil-square');
    expect($actions[2])->toBeInstanceOf(DeleteAction::class);
    expect($actions[2]->getIcon())->toBe('heroicon-m-trash');
});
