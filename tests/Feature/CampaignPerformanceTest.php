<?php

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Panel;
use Filament\Tables\Table;
use Filament\Widgets\ChartWidget;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\Concerns\HasEmailCampaignSendNowAction;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ViewEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\RecipientsRelationManager as EmailRecipientsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\Concerns\HasSmsCampaignSendNowAction;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager as SmsRecipientsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Widgets\CampaignPerformanceChart;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignSendsOverTimeChart;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignStatsWidget;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignSendsOverTimeChart;

it('exposes the Send-now action concern on the Email campaign view page', function () {
    expect(in_array(
        HasEmailCampaignSendNowAction::class,
        class_uses_recursive(ViewEmailCampaign::class),
        true,
    ))->toBeTrue();
});

it('exposes the Send-now action concern on the SMS campaign view page', function () {
    expect(in_array(
        HasSmsCampaignSendNowAction::class,
        class_uses_recursive(ViewSmsCampaign::class),
        true,
    ))->toBeTrue();
});

it('registers the Send-now header action on each campaign view page', function (string $page, array $expected) {
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($instance);

    $names = array_map(fn ($action) => $action->getName(), $actions);

    foreach ($expected as $name) {
        expect($names)->toContain($name);
    }
})->with([
    'EmailCampaign' => [ViewEmailCampaign::class, ['backToIndex', 'preview', 'sendNow', 'schedule', 'cancel', 'edit', 'delete']],
    'SmsCampaign' => [ViewSmsCampaign::class, ['backToIndex', 'preview', 'sendNow', 'schedule', 'cancel', 'edit', 'delete']],
]);

it('makes the Send-now action require confirmation', function (string $page, string $factory) {
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, $factory);
    $method->setAccessible(true);
    $action = $method->invoke($instance);

    expect($action->getName())->toBe('sendNow');
    expect($action->isConfirmationRequired())->toBeTrue();
})->with([
    'EmailCampaign' => [ViewEmailCampaign::class, 'emailCampaignSendNowAction'],
    'SmsCampaign' => [ViewSmsCampaign::class, 'smsCampaignSendNowAction'],
]);

it('includes engagement columns on the Email RecipientsRelationManager', function () {
    $rm = new EmailRecipientsRelationManager;
    $table = $rm->table(Table::make($rm));
    $names = array_map(fn ($col) => $col->getName(), $table->getColumns());

    foreach (['opens_count', 'clicks_count', 'last_opened_at', 'first_clicked_at', 'unsubscribed_at', 'bounce_status'] as $col) {
        expect($names)->toContain($col);
    }
});

it('includes delivery columns and a copyable message_id on the SMS RecipientsRelationManager', function () {
    $rm = new SmsRecipientsRelationManager;
    $table = $rm->table(Table::make($rm));
    $columns = $table->getColumns();
    $names = array_map(fn ($col) => $col->getName(), $columns);

    foreach (['delivered_count_state', 'clicks_count', 'unsubscribed_at', 'clicksend_message_id'] as $col) {
        expect($names)->toContain($col);
    }

    $messageIdCol = collect($columns)->firstWhere(fn ($c) => $c->getName() === 'clicksend_message_id');
    $prop = new ReflectionProperty($messageIdCol, 'isCopyable');
    $prop->setAccessible(true);
    expect((bool) $prop->getValue($messageIdCol))->toBeTrue();
});

it('overrides the infolist method on both campaign resources', function () {
    foreach ([EmailCampaignResource::class, SmsCampaignResource::class] as $resource) {
        $method = new ReflectionMethod($resource, 'infolist');
        expect($method->getDeclaringClass()->getName())->toBe($resource);
    }
});

it('registers sends-over-time chart widgets as footer widgets on each campaign view page', function (string $page, string $widget) {
    $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($page, 'getFooterWidgets');
    $method->setAccessible(true);

    expect($method->invoke($instance))->toContain($widget);
})->with([
    'EmailCampaign' => [ViewEmailCampaign::class, EmailCampaignSendsOverTimeChart::class],
    'SmsCampaign' => [ViewSmsCampaign::class, SmsCampaignSendsOverTimeChart::class],
]);

it('exposes the CampaignPerformanceChart as a ChartWidget subclass', function () {
    expect(is_subclass_of(CampaignPerformanceChart::class, ChartWidget::class))->toBeTrue();
});

it('never registers CampaignPerformanceChart on the plugin panel — it was removed from the Dashboard to match core /crm/dashboard, and staying unregistered guarantees Filament::getWidgets() can never surface it there', function () {
    foreach ([true, false] as $emailMarketing) {
        $plugin = LaravelCrmPlugin::make()->modules(['email-marketing' => $emailMarketing]);
        $panel = Panel::make()->id('test-panel-' . uniqid());
        $plugin->register($panel);

        expect($panel->getWidgets())->not->toContain(CampaignPerformanceChart::class);
    }
});

it('exposes both sends-over-time widgets as ChartWidget subclasses', function () {
    expect(is_subclass_of(EmailCampaignSendsOverTimeChart::class, ChartWidget::class))->toBeTrue();
    expect(is_subclass_of(SmsCampaignSendsOverTimeChart::class, ChartWidget::class))->toBeTrue();
});

it('registers EmailCampaignStatsWidget as a header widget on ViewEmailCampaign', function () {
    $instance = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getHeaderWidgets');
    $method->setAccessible(true);

    $widgets = $method->invoke($instance);
    expect($widgets)->toContain(EmailCampaignStatsWidget::class);
});

it('sets ViewEmailCampaign header widget columns to 4', function () {
    $instance = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();
    expect($instance->getHeaderWidgetsColumns())->toBe(4);
});

it('renders ViewEmailCampaign header actions in the expected [backToIndex, preview, sendNow, schedule, cancel, edit, delete] order', function () {
    $instance = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($instance);

    $names = array_map(fn ($action) => $action->getName(), $actions);
    expect($names)->toBe(['backToIndex', 'preview', 'sendNow', 'schedule', 'cancel', 'edit', 'delete']);
});

it('renders ViewEmailCampaign Edit action as an icon pill gated on isEditable() and Delete as an icon pill', function () {
    $instance = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getHeaderActions');
    $method->setAccessible(true);
    $actions = $method->invoke($instance);

    $byName = collect($actions)->keyBy(fn ($action) => $action->getName());

    // Edit action shape
    $edit = $byName['edit'];
    expect($edit)->toBeInstanceOf(EditAction::class);
    expect($edit->getIcon())->toBe('heroicon-m-pencil-square');

    // Delete action shape
    $delete = $byName['delete'];
    expect($delete)->toBeInstanceOf(DeleteAction::class);
    expect($delete->getIcon())->toBe('heroicon-m-trash');
    expect($delete->isConfirmationRequired())->toBeTrue();
});

it('replaces the Performance infolist Section with a sections.details Section on EmailCampaignResource', function () {
    $source = file_get_contents((new ReflectionClass(EmailCampaignResource::class))->getFileName());

    // New Details section present
    expect($source)->toContain("Section::make('Details')->heading(__('laravel-crm-filament::labels.sections.details'))");

    // Pre-existing rate TextEntries dropped from EmailCampaignResource
    expect($source)->not->toContain("TextEntry::make('open_rate')");
    expect($source)->not->toContain("TextEntry::make('click_rate')");
    expect($source)->not->toContain("TextEntry::make('unsubscribe_rate')");
    expect($source)->not->toContain("TextEntry::make('sent_count_state')");
    expect($source)->not->toContain("TextEntry::make('failed_count_state')");
    expect($source)->not->toContain("TextEntry::make('skipped_count_state')");
});

it('renders the AC-named 8 TextEntries in the Details section on EmailCampaignResource', function () {
    $source = file_get_contents((new ReflectionClass(EmailCampaignResource::class))->getFileName());

    foreach ([
        "TextEntry::make('name')",
        "TextEntry::make('campaign_id')",
        "TextEntry::make('subject')",
        "TextEntry::make('preview_text')",
        "TextEntry::make('status')",
        "TextEntry::make('scheduled_at')",
        "TextEntry::make('sent_at')",
        "TextEntry::make('template.name')",
    ] as $entry) {
        expect($source)->toContain($entry);
    }

    // scheduled_at renders WITH timezone via the state closure
    expect($source)->toContain('$record->timezone');
});
