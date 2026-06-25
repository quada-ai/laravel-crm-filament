<?php

use Filament\Panel;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmFilamentServiceProvider;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FeatureCommentsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FeatureVotersRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\CreateFeature;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\EditFeature;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\ListFeatures;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\ViewFeature;

it('binds FeatureResource to the Feature model with external_id routing and the features slug', function () {
    expect(FeatureResource::getModel())->toBe('VentureDrake\\LaravelCrm\\Models\\Feature');
    expect(FeatureResource::getSlug())->toBe('features');
    expect(FeatureResource::getRecordRouteKeyName())->toBe('external_id');
});

it('uses the shared trait family (UsesExternalIdRouting, HasLabels, HasCrmCustomFields, HasCrmCustomFieldEntries, HasPrimaryBulkActions)', function () {
    $traits = class_uses_recursive(FeatureResource::class);

    expect($traits)->toContain(UsesExternalIdRouting::class);
    expect($traits)->toContain(HasLabels::class);
    expect($traits)->toContain(HasCrmCustomFields::class);
    expect($traits)->toContain(HasCrmCustomFieldEntries::class);
    expect($traits)->toContain(HasPrimaryBulkActions::class);
});

it('exposes list, kanban, create, view, and edit pages on FeatureResource', function () {
    expect(array_keys(FeatureResource::getPages()))
        ->toEqual(['index', 'kanban', 'create', 'view', 'edit']);
});

it('routes Feature page classes back to FeatureResource', function () {
    foreach ([CreateFeature::class, EditFeature::class, ListFeatures::class, ViewFeature::class] as $page) {
        $reflection = new ReflectionProperty($page, 'resource');
        $reflection->setAccessible(true);
        expect($reflection->getValue())->toBe(FeatureResource::class);
    }
});

it('declares title + description + is_public + feature_status_id + user_owner_id + user_assigned_id + labels in the form', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toContain("Forms\\Components\\TextInput::make('title')");
    expect($source)->toContain("Forms\\Components\\Textarea::make('description')");
    expect($source)->toContain("Forms\\Components\\Toggle::make('is_public')");
    expect($source)->toContain("Forms\\Components\\Select::make('feature_status_id')");
    expect($source)->toContain("Forms\\Components\\Select::make('user_owner_id')");
    expect($source)->toContain("Forms\\Components\\Select::make('user_assigned_id')");
    expect($source)->toContain('static::labelsField()');
});

it('orders the feature_status_id Select by the status order column', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toContain('FeatureStatus::query()');
    expect($source)->toContain("->orderBy('order')");
});

it('renders the 7 core-aligned columns in source: created_at, feature_id, title, status.name, votes_count, comments_count, is_public', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toContain("Tables\\Columns\\TextColumn::make('created_at')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('feature_id')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('title')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('status.name')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('votes_count')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('comments_count')");
    expect($source)->toContain("Tables\\Columns\\TextColumn::make('is_public')");

    // Regression guard: dropped columns should not be present
    expect($source)->not->toContain("Tables\\Columns\\TextColumn::make('labels.name')");
    expect($source)->not->toContain("Tables\\Columns\\TextColumn::make('views_count')");
    expect($source)->not->toContain("Tables\\Columns\\TextColumn::make('ownerUser.name')");
    expect($source)->not->toContain("Tables\\Columns\\IconColumn::make('is_public')");
});

it('renders is_public as a Yes/No formatted TextColumn matching core CRM', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    // is_public uses TextColumn + formatStateUsing returning lang.yes/no — not IconColumn boolean
    expect($source)->toMatch("/TextColumn::make\\('is_public'\\)[\\s\\S]{0,400}->formatStateUsing/");
    expect($source)->toContain("__('laravel-crm::lang.yes')");
    expect($source)->toContain("__('laravel-crm::lang.no')");
});

it('reads votes_count/comments_count straight off the model without withCount duplication', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    // No withCount calls anywhere — these are persisted columns on crm_features
    // populated by core's vote/comment/view services.
    expect($source)->not->toContain('withCount(');
    expect($source)->not->toContain('->counts(');
});

it('renders the status badge with a color closure that reads status->color', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    // The status badge color closure pulls $record->status->color and prefixes '#'
    expect($source)->toMatch("/TextColumn::make\\('status\\.name'\\)[\\s\\S]*?->color\\(function/");
    expect($source)->toContain('$record?->status?->color');
});

it('declares default sort created_at desc and uses ->since() for created_at column', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toContain("->defaultSort('created_at', 'desc')");
    expect($source)->toContain('->since()');
});

it('registers exactly the 2 core-aligned filters: feature_status_id (Status) and is_public (Visibility)', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toContain("Tables\\Filters\\SelectFilter::make('feature_status_id')");
    expect($source)->toContain("Tables\\Filters\\TernaryFilter::make('is_public')");

    // Regression guard: owner + labels filters are dropped to match core's drawer
    expect($source)->not->toContain("Tables\\Filters\\SelectFilter::make('user_owner_id')");
    expect($source)->not->toContain("Tables\\Filters\\SelectFilter::make('labels')");
});

it('registers View, Edit, Delete record actions with Delete requiring confirmation', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    expect($source)->toMatch('/recordActions\\(\\[(?:\\s|\\S)*?Actions\\\\ViewAction::make\\(\\)(?:\\s|\\S)*?Actions\\\\EditAction::make\\(\\)(?:\\s|\\S)*?Actions\\\\DeleteAction::make\\(\\)(?:\\s|\\S)*?->requiresConfirmation\\(\\)/');
});

it('returns the 10 AC-named relation managers in getRelations()', function () {
    expect(FeatureResource::getRelations())->toEqual([
        CrmActivitiesRelationManager::class,
        CrmNotesRelationManager::class,
        CrmTasksRelationManager::class,
        CrmCallsRelationManager::class,
        CrmMeetingsRelationManager::class,
        CrmLunchesRelationManager::class,
        CrmFilesRelationManager::class,
        FeatureVotersRelationManager::class,
        FeatureCommentsRelationManager::class,
        AuditsRelationManager::class,
    ]);
});

it('declares an infolist override that wraps Details and Custom fields sections', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    // Infolist override must be declared locally
    $reflection = new ReflectionMethod(FeatureResource::class, 'infolist');
    expect($reflection->getDeclaringClass()->getName())->toBe(FeatureResource::class);

    expect($source)->toContain("Section::make(__('laravel-crm-filament::labels.sections.details'))");
    expect($source)->toContain("Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))");
    expect($source)->toContain('static::crmCustomFieldEntries($record, false)');
    expect($source)->toContain('static::crmCustomFieldEntries($record, true)');
});

// AC: Details section contains exactly the 6 AC-named TextEntries in the prescribed
// order. Cursor-walked strpos sequence asserts every TextEntry::make literal appears
// in the right relative position — catches a future reorder OR removal in one
// assertion. Modeled on ProductInfolistTest::it('infolist source contains Details +
// Custom fields section headings in order').
it('renders the 6 AC-named Details TextEntries in feature_id, created_at, description, submittedBy, ownerUser, status order', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    // Restrict the search to the Details Section closure body so the test does not
    // accidentally match the same TextEntry-shaped strings if Custom fields ever
    // grew to reference these names. The Details closure runs from the
    // ->schema(fn (?Feature $record) => array_merge([ marker through the matching
    // ], $record ? static::crmCustomFieldEntries($record, false) : []) closure tail.
    $detailsStart = strpos($source, '->schema(fn (?Feature $record) => array_merge([');
    $detailsEnd = strpos($source, '], $record ? static::crmCustomFieldEntries($record, false)', $detailsStart);
    expect($detailsStart)->not->toBeFalse();
    expect($detailsEnd)->not->toBeFalse();
    $detailsBody = substr($source, $detailsStart, $detailsEnd - $detailsStart);

    $needles = [
        "TextEntry::make('feature_id')",
        "TextEntry::make('created_at')",
        "TextEntry::make('description')",
        "TextEntry::make('submittedBy.name')",
        "TextEntry::make('ownerUser.name')",
        "TextEntry::make('status.name')",
    ];

    $cursor = 0;
    foreach ($needles as $needle) {
        $pos = strpos($detailsBody, $needle, $cursor);
        expect($pos)->not->toBeFalse(
            "Expected to find {$needle} in the Details section closure at or after position {$cursor}"
        );
        $cursor = $pos + strlen($needle);
    }
});

// AC: The Details section source MUST NOT reference the 6 dropped fields. Confined
// to the Details Section closure so the table()'s votes/comments/is_public columns
// stay untouched.
it('drops is_public, votes_count, comments_count, views_count, labels.name, and assignedToUser.name from the Details section closure', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    $detailsStart = strpos($source, '->schema(fn (?Feature $record) => array_merge([');
    $detailsEnd = strpos($source, '], $record ? static::crmCustomFieldEntries($record, false)', $detailsStart);
    expect($detailsStart)->not->toBeFalse();
    expect($detailsEnd)->not->toBeFalse();
    $detailsBody = substr($source, $detailsStart, $detailsEnd - $detailsStart);

    expect($detailsBody)->not->toContain("TextEntry::make('is_public')");
    expect($detailsBody)->not->toContain("TextEntry::make('votes_count')");
    expect($detailsBody)->not->toContain("TextEntry::make('comments_count')");
    expect($detailsBody)->not->toContain("TextEntry::make('views_count')");
    expect($detailsBody)->not->toContain("TextEntry::make('labels.name')");
    expect($detailsBody)->not->toContain("TextEntry::make('assignedToUser.name')");
});

// AC: Custom Fields section + its closure-typed ->hidden() gate must be preserved
// byte-for-byte. Locks the exact contract via a single literal-source assertion —
// any reformat/refactor that drifts the section's shape will trip this guard.
it('preserves the Custom Fields section + its ->hidden() gate byte-for-byte', function () {
    $source = file_get_contents((new ReflectionClass(FeatureResource::class))->getFileName());

    $expected = <<<'PHP'
Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Feature $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Feature) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                })
PHP;

    expect($source)->toContain($expected);
});

it('routes Create/Edit pages through FeatureService::create() and update()', function () {
    $createSource = file_get_contents((new ReflectionClass(CreateFeature::class))->getFileName());
    $editSource = file_get_contents((new ReflectionClass(EditFeature::class))->getFileName());

    expect($createSource)->toContain('FeatureService::class');
    expect($createSource)->toContain('->create(');

    expect($editSource)->toContain('FeatureService::class');
    expect($editSource)->toContain('->update(');
});

it('overrides ViewFeature::content() with the canonical 2-col Grid (infolist left, RMs right)', function () {
    $source = file_get_contents((new ReflectionClass(ViewFeature::class))->getFileName());

    expect($source)->toContain("Grid::make(['default' => 1, 'lg' => 2])");
    expect($source)->toContain('getInfolistContentComponent()');
    expect($source)->toContain('getRelationManagersContentComponent()');
    expect($source)->toContain("->columnSpan(['lg' => 1])");

    // content() must be declared on ViewFeature itself, not inherited
    $reflection = new ReflectionMethod(ViewFeature::class, 'content');
    expect($reflection->getDeclaringClass()->getName())->toBe(ViewFeature::class);
});

it('ViewFeature::getTitle() returns the bare title attribute', function () {
    $source = file_get_contents((new ReflectionClass(ViewFeature::class))->getFileName());

    expect($source)->toContain('public function getTitle()');
    expect($source)->toContain('$this->record?->title');
});

it('ViewFeature::getHeaderActions() returns Back, Edit, Delete in that order', function () {
    $source = file_get_contents((new ReflectionClass(ViewFeature::class))->getFileName());

    expect($source)->toContain('FeatureResource::backToIndexAction()');
    expect($source)->toContain('Actions\\EditAction::make()');
    expect($source)->toContain('Actions\\DeleteAction::make()');

    // Back must come before Edit which must come before Delete in source order
    $backPos = strpos($source, 'FeatureResource::backToIndexAction()');
    $editPos = strpos($source, 'Actions\\EditAction::make()');
    $deletePos = strpos($source, 'Actions\\DeleteAction::make()');

    expect($backPos)->toBeLessThan($editPos);
    expect($editPos)->toBeLessThan($deletePos);
});

it('declares backToIndexAction and listKanbanToggleActions factories on FeatureResource', function () {
    expect(method_exists(FeatureResource::class, 'backToIndexAction'))->toBeTrue();
    expect(method_exists(FeatureResource::class, 'listKanbanToggleActions'))->toBeTrue();
});

it('gates FeatureResource registration on the features module flag', function () {
    // With the features module on, the resource should be registered.
    $plugin = LaravelCrmPlugin::make()->modules(['features' => true]);
    $panel = Panel::make()->id('admin-features-on')->default();
    $plugin->register($panel);

    expect($panel->getResources())->toContain(FeatureResource::class);

    // With the features module off, the resource should NOT be registered.
    $plugin = LaravelCrmPlugin::make()->modules(['features' => false]);
    $panel = Panel::make()->id('admin-features-off')->default();
    $plugin->register($panel);

    expect($panel->getResources())->not->toContain(FeatureResource::class);
});

it('adds Feature::class to auditableModels() and timelineActivityModels()', function () {
    expect(LaravelCrmFilamentServiceProvider::auditableModels())
        ->toContain('VentureDrake\\LaravelCrm\\Models\\Feature');

    expect(LaravelCrmFilamentServiceProvider::timelineActivityModels())
        ->toContain('VentureDrake\\LaravelCrm\\Models\\Feature');
});

it('guards the polyfill loops in packageBooted() with class_exists so optional models do not break boot', function () {
    $source = file_get_contents((new ReflectionClass(LaravelCrmFilamentServiceProvider::class))->getFileName());

    // Both foreach loops over auditableModels / timelineActivityModels should branch on class_exists.
    expect($source)->toMatch('/foreach \\(static::auditableModels\\(\\) as \\$auditableModel\\) \\{\\s*if \\(! class_exists\\(\\$auditableModel\\)\\)/');
    expect($source)->toMatch('/foreach \\(static::timelineActivityModels\\(\\) as \\$timelineModel\\) \\{\\s*if \\(! class_exists\\(\\$timelineModel\\)\\)/');
});
