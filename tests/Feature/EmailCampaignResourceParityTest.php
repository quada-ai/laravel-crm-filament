<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\CreateEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\EditEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ListEmailCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ViewEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\ClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\RecipientsRelationManager;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignSendsOverTimeChart;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignStatsWidget;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignTopUrlsWidget;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'EmailCampaign Parity Tester',
        'email' => 'email-campaign-parity-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('uses the UsesExternalIdRouting trait AND inherits getRecordRouteKeyName + getUrl from the trait file', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(EmailCampaignResource::class), true))
        ->toBeTrue();

    expect(EmailCampaignResource::getRecordRouteKeyName())->toBe('external_id');

    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(EmailCampaignResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(EmailCampaignResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(EmailCampaignResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = EmailCampaignResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(EmailCampaignResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_email_campaigns'));
});

it('renders the 7 source-parity table columns in exact order with campaign_id first and no unique_opens_count', function (): void {
    /** @var ListEmailCampaigns $instance */
    $instance = livewire(ListEmailCampaigns::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $names = [];
    foreach ($columns as $col) {
        $names[] = $col->getName();
    }

    expect($names)->toBe([
        'campaign_id',
        'name',
        'subject',
        'status',
        'total_recipients',
        'scheduled_at',
        'sent_at',
    ])
        ->and($names)->toContain('campaign_id')
        ->and($names)->not->toContain('unique_opens_count');
});

it('table source has ->since() on both scheduled_at + sent_at and the AC-named OR-searchable closure on name', function (): void {
    $src = file_get_contents((new ReflectionClass(EmailCampaignResource::class))->getFileName());

    // Two ->since() timestamp columns
    expect(substr_count($src, '->since()'))->toBeGreaterThanOrEqual(2);

    // scheduled_at + sent_at both present
    expect($src)->toContain("TextColumn::make('scheduled_at')")
        ->and($src)->toContain("TextColumn::make('sent_at')");

    // Complex searchable closure on name — OR across name / subject / campaign_id
    expect($src)->toContain('searchable(query: function (Builder $query, string $search): Builder {')
        ->and($src)->toContain("->where('name', 'like', \"%{\$search}%\")")
        ->and($src)->toContain("->orWhere('subject', 'like', \"%{\$search}%\")")
        ->and($src)->toContain("->orWhere('campaign_id', 'like', \"%{\$search}%\")");
});

it('recordActions register ViewAction -> EditAction -> DeleteAction with button()->hiddenLabel() and Delete requires confirmation', function (): void {
    $src = file_get_contents((new ReflectionClass(EmailCampaignResource::class))->getFileName());

    expect($src)->toContain('Actions\\ViewAction::make()->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\EditAction::make()->visible(fn ($record) => $record->isEditable())->button()->hiddenLabel()')
        ->and($src)->toContain('Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');

    $viewPos = strpos($src, 'Actions\\ViewAction::make()->button()->hiddenLabel()');
    $editPos = strpos($src, 'Actions\\EditAction::make()->visible(fn ($record) => $record->isEditable())->button()->hiddenLabel()');
    $deletePos = strpos($src, 'Actions\\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation()');

    expect($viewPos)->not->toBeFalse()
        ->and($editPos)->not->toBeFalse()
        ->and($deletePos)->not->toBeFalse()
        ->and($viewPos)->toBeLessThan($editPos)
        ->and($editPos)->toBeLessThan($deletePos);
});

it('ViewEmailCampaign header actions render in [backToIndex, preview, sendNow, schedule, cancel, edit, delete] order with correct pencil + trash icons', function (): void {
    expect(is_subclass_of(ViewEmailCampaign::class, ViewRecord::class))->toBeTrue();

    $page = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    $names = array_map(fn ($action) => $action->getName(), $actions);
    expect($names)->toBe(['backToIndex', 'preview', 'sendNow', 'schedule', 'cancel', 'edit', 'delete']);

    $byName = collect($actions)->keyBy(fn ($action) => $action->getName());

    expect($byName['backToIndex'])->toBeInstanceOf(Actions\Action::class)
        ->and($byName['backToIndex']->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($byName['edit'])->toBeInstanceOf(Actions\EditAction::class)
        ->and($byName['edit']->getIcon())->toBe('heroicon-m-pencil-square')
        ->and($byName['delete'])->toBeInstanceOf(Actions\DeleteAction::class)
        ->and($byName['delete']->getIcon())->toBe('heroicon-m-trash')
        ->and($byName['delete']->isConfirmationRequired())->toBeTrue();
});

it('EditEmailCampaign header actions render in [backToIndex, view, delete] order', function (): void {
    expect(is_subclass_of(EditEmailCampaign::class, EditRecord::class))->toBeTrue();

    $page = (new ReflectionClass(EditEmailCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(EditEmailCampaign::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Actions\Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(Actions\ViewAction::class)
        ->and($actions[2])->toBeInstanceOf(Actions\DeleteAction::class);
});

it('EmailCampaignStatsWidget extends StatsOverviewWidget with columnSpan=full, getColumns=4, and 4 em-dash stats on a null record', function (): void {
    expect(is_subclass_of(EmailCampaignStatsWidget::class, StatsOverviewWidget::class))->toBeTrue();

    // columnSpan static property
    $spanRef = new ReflectionProperty(EmailCampaignStatsWidget::class, 'columnSpan');
    $spanRef->setAccessible(true);
    $widget = (new ReflectionClass(EmailCampaignStatsWidget::class))->newInstanceWithoutConstructor();
    expect($spanRef->getValue($widget))->toBe('full');

    // Public nullable ?EmailCampaign $record with null default
    $recordRef = new ReflectionProperty(EmailCampaignStatsWidget::class, 'record');
    expect($recordRef->isPublic())->toBeTrue()
        ->and($recordRef->getType()?->getName())->toBe(EmailCampaign::class)
        ->and($recordRef->getType()?->allowsNull())->toBeTrue()
        ->and($recordRef->getValue($widget))->toBeNull();

    // getColumns() === 4
    $colsMethod = new ReflectionMethod(EmailCampaignStatsWidget::class, 'getColumns');
    $colsMethod->setAccessible(true);
    expect($colsMethod->invoke($widget))->toBe(4);

    // Null-record path — 4 Stats, each with em-dash value
    $statsMethod = new ReflectionMethod(EmailCampaignStatsWidget::class, 'getStats');
    $statsMethod->setAccessible(true);
    $stats = $statsMethod->invoke($widget);

    expect($stats)->toHaveCount(4);
    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(Stat::class)
            ->and($stat->getValue())->toBe('—');
    }
});

it('registers EmailCampaignStatsWidget as a header widget on ViewEmailCampaign with getHeaderWidgetsColumns === 4', function (): void {
    $page = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getHeaderWidgets');
    $method->setAccessible(true);

    expect($method->invoke($page))->toContain(EmailCampaignStatsWidget::class);
    expect($page->getHeaderWidgetsColumns())->toBe(4);
});

it('EmailCampaignResource::infolist() declares a Details section with the 9 AC-named TextEntries and the old 6 rate TextEntries are absent', function (): void {
    $src = file_get_contents((new ReflectionClass(EmailCampaignResource::class))->getFileName());

    // Details section heading routed through the labels namespace
    expect($src)->toContain("Section::make('Details')->heading(__('laravel-crm-filament::labels.sections.details'))");

    // 9 AC-named TextEntries all present
    foreach ([
        "TextEntry::make('name')",
        "TextEntry::make('campaign_id')",
        "TextEntry::make('subject')",
        "TextEntry::make('preview_text')",
        "TextEntry::make('status')",
        "TextEntry::make('scheduled_at')",
        "TextEntry::make('sent_at')",
        "TextEntry::make('template.name')",
        "TextEntry::make('ownerUser.name')",
    ] as $entry) {
        expect($src)->toContain($entry);
    }

    // scheduled_at state closure appends timezone
    expect($src)->toContain('$record->timezone');

    // Pre-existing 6 rate TextEntries dropped from the resource
    foreach ([
        "TextEntry::make('open_rate')",
        "TextEntry::make('click_rate')",
        "TextEntry::make('unsubscribe_rate')",
        "TextEntry::make('sent_count_state')",
        "TextEntry::make('failed_count_state')",
        "TextEntry::make('skipped_count_state')",
    ] as $entry) {
        expect($src)->not->toContain($entry);
    }
});

it('CreateEmailCampaign + EditEmailCampaign route service payloads through FormPayload::wrap(...)->toArray()', function (): void {
    $createSrc = file_get_contents((new ReflectionClass(CreateEmailCampaign::class))->getFileName());
    $editSrc = file_get_contents((new ReflectionClass(EditEmailCampaign::class))->getFileName());

    // Import present on both pages
    expect($createSrc)->toContain('use VentureDrake\\LaravelCrmFilament\\Support\\FormPayload;')
        ->and($editSrc)->toContain('use VentureDrake\\LaravelCrmFilament\\Support\\FormPayload;');

    // Service call goes through FormPayload::wrap($data)->toArray() on both pages
    expect($createSrc)->toContain('FormPayload::wrap($data)->toArray()')
        ->and($editSrc)->toContain('FormPayload::wrap($data)->toArray()');

    // Create routes through EmailCampaignService::create; Edit through ->update
    expect($createSrc)->toContain('EmailCampaignService::class')
        ->and($createSrc)->toContain('->create(FormPayload::wrap($data)->toArray())')
        ->and($editSrc)->toContain('EmailCampaignService::class')
        ->and($editSrc)->toContain('->update(FormPayload::wrap($data)->toArray(), $record)');
});

it('preserves the two RelationManagers (Recipients + Clicks) and both footer widgets on ViewEmailCampaign', function (): void {
    // getRelations() contract preserved verbatim
    expect(EmailCampaignResource::getRelations())->toBe([
        RecipientsRelationManager::class,
        ClicksRelationManager::class,
    ]);

    // Footer widgets: sends-over-time chart + top URLs widget
    $page = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getFooterWidgets');
    $method->setAccessible(true);
    $widgets = $method->invoke($page);

    expect($widgets)->toContain(EmailCampaignSendsOverTimeChart::class)
        ->and($widgets)->toContain(EmailCampaignTopUrlsWidget::class);
});

it('en/fr/es label files declare actions.back_to_email_campaigns with non-empty values', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_email_campaigns'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_email_campaigns'])->not->toBe('');
    }
});
