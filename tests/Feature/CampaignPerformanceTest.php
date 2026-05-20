<?php

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
    'EmailCampaign' => [ViewEmailCampaign::class, ['sendNow', 'preview', 'schedule', 'cancel']],
    'SmsCampaign' => [ViewSmsCampaign::class, ['sendNow', 'preview', 'schedule', 'cancel']],
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

it('registers CampaignPerformanceChart on the plugin panel when email marketing is enabled', function () {
    $plugin = LaravelCrmPlugin::make()
        ->modules(['email-marketing' => true]);

    $panel = Panel::make()->id('test-panel-' . uniqid());
    $plugin->register($panel);

    $widgets = $panel->getWidgets();
    expect($widgets)->toContain(CampaignPerformanceChart::class);
});

it('does not register CampaignPerformanceChart when email marketing is disabled', function () {
    $plugin = LaravelCrmPlugin::make()
        ->modules(['email-marketing' => false]);

    $panel = Panel::make()->id('test-panel-disabled-' . uniqid());
    $plugin->register($panel);

    $widgets = $panel->getWidgets();
    expect($widgets)->not->toContain(CampaignPerformanceChart::class);
});

it('exposes both sends-over-time widgets as ChartWidget subclasses', function () {
    expect(is_subclass_of(EmailCampaignSendsOverTimeChart::class, ChartWidget::class))->toBeTrue();
    expect(is_subclass_of(SmsCampaignSendsOverTimeChart::class, ChartWidget::class))->toBeTrue();
});
