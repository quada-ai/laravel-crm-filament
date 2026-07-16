<?php

declare(strict_types=1);

use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\CreateSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\EditSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ListSmsCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\ClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignSendsOverTimeChart;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignStatsWidget;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignTopUrlsWidget;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'SmsCampaign Parity Tester',
        'email' => 'sms-campaign-parity-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('uses the UsesExternalIdRouting trait AND inherits getRecordRouteKeyName + getUrl from the trait file', function (): void {
    expect(in_array(UsesExternalIdRouting::class, class_uses_recursive(SmsCampaignResource::class), true))
        ->toBeTrue();

    expect(SmsCampaignResource::getRecordRouteKeyName())->toBe('external_id');

    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    $keyMethod = new ReflectionMethod(SmsCampaignResource::class, 'getRecordRouteKeyName');
    expect($keyMethod->getFileName())->toBe($traitFile);

    $urlMethod = new ReflectionMethod(SmsCampaignResource::class, 'getUrl');
    expect($urlMethod->getFileName())->toBe($traitFile);
});

it('declares backToIndexAction as a public static factory returning a gray back-arrow Action', function (): void {
    $ref = new ReflectionMethod(SmsCampaignResource::class, 'backToIndexAction');
    expect($ref->isPublic())->toBeTrue()
        ->and($ref->isStatic())->toBeTrue()
        ->and($ref->getNumberOfParameters())->toBe(0);

    $action = SmsCampaignResource::backToIndexAction();
    expect($action)->toBeInstanceOf(Actions\Action::class)
        ->and($action->getName())->toBe('backToIndex')
        ->and($action->getColor())->toBe('gray')
        ->and($action->getIcon())->toBe('heroicon-o-arrow-left')
        ->and($action->getUrl())->toBe(SmsCampaignResource::getUrl('index'))
        ->and($action->getLabel())->toBe(__('laravel-crm-filament::labels.actions.back_to_sms_campaigns'));
});

it('renders the 7 source-parity table columns in exact order with campaign_id first and no delivered_count/failed_count', function (): void {
    /** @var ListSmsCampaigns $instance */
    $instance = livewire(ListSmsCampaigns::class)->instance();

    $columns = $instance->getTable()->getColumns();

    $names = [];
    foreach ($columns as $col) {
        $names[] = $col->getName();
    }

    expect($names)->toBe([
        'campaign_id',
        'name',
        'from',
        'status',
        'total_recipients',
        'scheduled_at',
        'sent_at',
    ])
        ->and($names)->toContain('campaign_id')
        ->and($names)->toContain('from')
        ->and($names)->not->toContain('delivered_count')
        ->and($names)->not->toContain('failed_count');
});

it('table source has ->since() on both scheduled_at + sent_at and the AC-named OR-searchable closure on name (body instead of subject)', function (): void {
    $src = file_get_contents((new ReflectionClass(SmsCampaignResource::class))->getFileName());

    // Two ->since() timestamp columns
    expect(substr_count($src, '->since()'))->toBeGreaterThanOrEqual(2);

    // scheduled_at + sent_at both present
    expect($src)->toContain("TextColumn::make('scheduled_at')")
        ->and($src)->toContain("TextColumn::make('sent_at')");

    // Complex searchable closure on name — OR across name / body / campaign_id (SMS has body, not subject)
    expect($src)->toContain('searchable(query: function (Builder $query, string $search): Builder {')
        ->and($src)->toContain("->where('name', 'like', \"%{\$search}%\")")
        ->and($src)->toContain("->orWhere('body', 'like', \"%{\$search}%\")")
        ->and($src)->toContain("->orWhere('campaign_id', 'like', \"%{\$search}%\")");
});

it('recordActions register ViewAction -> EditAction -> DeleteAction with button()->hiddenLabel() and isEditable() gates Edit and Delete requires confirmation', function (): void {
    $src = file_get_contents((new ReflectionClass(SmsCampaignResource::class))->getFileName());

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

it('ViewSmsCampaign header actions render in [backToIndex, preview, sendNow, schedule, cancel, edit, delete] order with correct icons AND registers SmsCampaignStatsWidget with 4-column header widget columns', function (): void {
    expect(is_subclass_of(ViewSmsCampaign::class, ViewRecord::class))->toBeTrue();

    $page = (new ReflectionClass(ViewSmsCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewSmsCampaign::class, 'getHeaderActions');
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

    // Header widget registration + 4-column layout
    $hwMethod = new ReflectionMethod(ViewSmsCampaign::class, 'getHeaderWidgets');
    $hwMethod->setAccessible(true);

    expect($hwMethod->invoke($page))->toContain(SmsCampaignStatsWidget::class)
        ->and($page->getHeaderWidgetsColumns())->toBe(4);
});

it('EditSmsCampaign header actions render in [backToIndex, view, delete] order', function (): void {
    expect(is_subclass_of(EditSmsCampaign::class, EditRecord::class))->toBeTrue();

    $page = (new ReflectionClass(EditSmsCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(EditSmsCampaign::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($page);

    expect($actions)->toHaveCount(3)
        ->and($actions[0])->toBeInstanceOf(Actions\Action::class)
        ->and($actions[0]->getName())->toBe('backToIndex')
        ->and($actions[1])->toBeInstanceOf(Actions\ViewAction::class)
        ->and($actions[2])->toBeInstanceOf(Actions\DeleteAction::class);
});

it('SmsCampaignStatsWidget extends StatsOverviewWidget with columnSpan=full, getColumns=4, and 4 em-dash stats (Recipients/Delivered/Clicks/Unsubscribed) on a null record', function (): void {
    expect(is_subclass_of(SmsCampaignStatsWidget::class, StatsOverviewWidget::class))->toBeTrue();

    // columnSpan static property
    $spanRef = new ReflectionProperty(SmsCampaignStatsWidget::class, 'columnSpan');
    $spanRef->setAccessible(true);
    $widget = (new ReflectionClass(SmsCampaignStatsWidget::class))->newInstanceWithoutConstructor();
    expect($spanRef->getValue($widget))->toBe('full');

    // Public nullable ?SmsCampaign $record with null default
    $recordRef = new ReflectionProperty(SmsCampaignStatsWidget::class, 'record');
    expect($recordRef->isPublic())->toBeTrue()
        ->and($recordRef->getType()?->getName())->toBe(SmsCampaign::class)
        ->and($recordRef->getType()?->allowsNull())->toBeTrue()
        ->and($recordRef->getValue($widget))->toBeNull();

    // getColumns() === 4
    $colsMethod = new ReflectionMethod(SmsCampaignStatsWidget::class, 'getColumns');
    $colsMethod->setAccessible(true);
    expect($colsMethod->invoke($widget))->toBe(4);

    // Null-record path — 4 Stats, each with em-dash value
    $statsMethod = new ReflectionMethod(SmsCampaignStatsWidget::class, 'getStats');
    $statsMethod->setAccessible(true);
    $stats = $statsMethod->invoke($widget);

    expect($stats)->toHaveCount(4);
    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(Stat::class)
            ->and($stat->getValue())->toBe('—');
    }

    // Source-grep confirms SMS-native metric set (Delivered instead of Opens)
    $widgetSrc = file_get_contents((new ReflectionClass(SmsCampaignStatsWidget::class))->getFileName());
    expect($widgetSrc)->toContain('labels.campaign.recipients')
        ->and($widgetSrc)->toContain('labels.campaign.delivered')
        ->and($widgetSrc)->toContain('labels.campaign.clicks')
        ->and($widgetSrc)->toContain('labels.campaign.unsubscribed');
});

it('SmsCampaignResource::infolist() declares a Details section with the 8 AC-named TextEntries (no subject/preview_text) and the old 6 rate TextEntries are absent', function (): void {
    $src = file_get_contents((new ReflectionClass(SmsCampaignResource::class))->getFileName());

    // Details section heading routed through the labels namespace
    expect($src)->toContain("Section::make('Details')->heading(__('laravel-crm-filament::labels.sections.details'))");

    // 8 AC-named TextEntries all present (SMS has body/from instead of subject/preview_text)
    foreach ([
        "TextEntry::make('name')",
        "TextEntry::make('campaign_id')",
        "TextEntry::make('from')",
        "TextEntry::make('body')",
        "TextEntry::make('status')",
        "TextEntry::make('scheduled_at')",
        "TextEntry::make('sent_at')",
        "TextEntry::make('template.name')",
    ] as $entry) {
        expect($src)->toContain($entry);
    }

    // scheduled_at state closure appends timezone
    expect($src)->toContain('$record->timezone');

    // Pre-existing 6 rate/count TextEntries dropped from the resource
    foreach ([
        "TextEntry::make('sent_count')",
        "TextEntry::make('failed_count_state')",
        "TextEntry::make('skipped_count_state')",
        "TextEntry::make('delivery_rate')",
        "TextEntry::make('click_rate')",
        "TextEntry::make('unsubscribe_rate')",
    ] as $entry) {
        expect($src)->not->toContain($entry);
    }

    // Email-only fields (subject, preview_text) must NOT appear on the SMS infolist
    expect($src)->not->toContain("TextEntry::make('subject')")
        ->and($src)->not->toContain("TextEntry::make('preview_text')");
});

it('CreateSmsCampaign + EditSmsCampaign route service payloads through FormPayload::wrap(...)->toArray()', function (): void {
    $createSrc = file_get_contents((new ReflectionClass(CreateSmsCampaign::class))->getFileName());
    $editSrc = file_get_contents((new ReflectionClass(EditSmsCampaign::class))->getFileName());

    // Import present on both pages
    expect($createSrc)->toContain('use VentureDrake\\LaravelCrmFilament\\Support\\FormPayload;')
        ->and($editSrc)->toContain('use VentureDrake\\LaravelCrmFilament\\Support\\FormPayload;');

    // Service call goes through FormPayload::wrap($data)->toArray() on both pages
    expect($createSrc)->toContain('FormPayload::wrap($data)->toArray()')
        ->and($editSrc)->toContain('FormPayload::wrap($data)->toArray()');

    // Create routes through SmsCampaignService::create; Edit through ->update
    expect($createSrc)->toContain('SmsCampaignService::class')
        ->and($createSrc)->toContain('->create(FormPayload::wrap($data)->toArray())')
        ->and($editSrc)->toContain('SmsCampaignService::class')
        ->and($editSrc)->toContain('->update(FormPayload::wrap($data)->toArray(), $record)');
});

it('form Placeholder panel contains exactly the 4 SMS tokens (first_name, last_name, full_name, company_name) with no email token', function (): void {
    $src = file_get_contents((new ReflectionClass(SmsCampaignResource::class))->getFileName());

    // Placeholder component present + placeholdersPanelHtml() static helper wired via HtmlString
    expect($src)->toContain("Placeholder::make('available_placeholders')")
        ->and($src)->toContain('static::placeholdersPanelHtml()')
        ->and($src)->toContain('new HtmlString(static::placeholdersPanelHtml())');

    // Invoke the static helper to grab the rendered token panel HTML
    $ref = new ReflectionMethod(SmsCampaignResource::class, 'placeholdersPanelHtml');
    $ref->setAccessible(true);
    $html = $ref->invoke(null);

    // 4 SMS tokens present
    foreach (['first_name', 'last_name', 'full_name', 'company_name'] as $token) {
        expect($html)->toContain('{' . $token . '}');
    }

    // Email token must NOT be present in the SMS Placeholder panel
    expect($html)->not->toContain('{email}');
});

it('form Send Section wraps a Radio(send_mode) + a conditional DateTimePicker(scheduled_at) visible when send_mode === schedule_send', function (): void {
    $src = file_get_contents((new ReflectionClass(SmsCampaignResource::class))->getFileName());

    // Section 'Send' present with columns(2) and columnSpanFull
    expect($src)->toContain("Section::make('Send')");

    // Radio component with send_now / schedule_send options + default send_now + dehydrated(false)
    expect($src)->toContain("Forms\\Components\\Radio::make('send_mode')")
        ->and($src)->toContain("'send_now' => 'Send now'")
        ->and($src)->toContain("'schedule_send' => 'Schedule send'")
        ->and($src)->toContain("->default('send_now')")
        ->and($src)->toContain('->dehydrated(false)');

    // DateTimePicker for scheduled_at with visible + required conditional on send_mode === schedule_send
    expect($src)->toContain("Forms\\Components\\DateTimePicker::make('scheduled_at')")
        ->and($src)->toContain("->visible(fn (\$get): bool => \$get('send_mode') === 'schedule_send')")
        ->and($src)->toContain("->required(fn (\$get): bool => \$get('send_mode') === 'schedule_send')");
});

it('preserves the two RelationManagers (Recipients + Clicks) + both footer widgets on ViewSmsCampaign AND en/fr/es label parity for actions.back_to_sms_campaigns', function (): void {
    // getRelations() contract preserved verbatim
    expect(SmsCampaignResource::getRelations())->toBe([
        RecipientsRelationManager::class,
        ClicksRelationManager::class,
    ]);

    // Footer widgets: sends-over-time chart + top URLs widget
    $page = (new ReflectionClass(ViewSmsCampaign::class))->newInstanceWithoutConstructor();

    $method = new ReflectionMethod(ViewSmsCampaign::class, 'getFooterWidgets');
    $method->setAccessible(true);
    $widgets = $method->invoke($page);

    expect($widgets)->toContain(SmsCampaignSendsOverTimeChart::class)
        ->and($widgets)->toContain(SmsCampaignTopUrlsWidget::class);

    // en/fr/es label parity for actions.back_to_sms_campaigns
    $root = dirname(__DIR__, 2);

    foreach (['en', 'fr', 'es'] as $locale) {
        $labels = require $root . '/resources/lang/' . $locale . '/labels.php';

        expect($labels['actions']['back_to_sms_campaigns'] ?? null)->toBeString()
            ->and($labels['actions']['back_to_sms_campaigns'])->not->toBe('');
    }
});
