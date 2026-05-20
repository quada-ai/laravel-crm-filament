<?php

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignClick;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignClick;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ViewEmailCampaign;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\ClicksRelationManager as EmailClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ViewSmsCampaign;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\ClicksRelationManager as SmsClicksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignTopUrlsWidget;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignTopUrlsWidget;

it('registers the Email ClicksRelationManager on the EmailCampaignResource', function () {
    expect(EmailCampaignResource::getRelations())->toContain(EmailClicksRelationManager::class);
});

it('registers the SMS ClicksRelationManager on the SmsCampaignResource', function () {
    expect(SmsCampaignResource::getRelations())->toContain(SmsClicksRelationManager::class);
});

it('declares the relationship as clicks on both relation managers', function (string $rmClass) {
    $reflection = new ReflectionClass($rmClass);
    $property = $reflection->getProperty('relationship');
    expect($property->getValue())->toBe('clicks');
})->with([
    'email' => [EmailClicksRelationManager::class],
    'sms' => [SmsClicksRelationManager::class],
]);

it('makes both ClicksRelationManagers read-only', function (string $rmClass) {
    $instance = (new ReflectionClass($rmClass))->newInstanceWithoutConstructor();
    expect($instance->isReadOnly())->toBeTrue();

    $source = file_get_contents((new ReflectionClass($rmClass))->getFileName());
    expect($source)->toContain('->headerActions([])');
    expect($source)->toContain('->recordActions([])');
    expect($source)->toContain('->toolbarActions([])');
})->with([
    'email' => [EmailClicksRelationManager::class],
    'sms' => [SmsClicksRelationManager::class],
]);

it('exposes per-recipient + per-URL columns on the Email ClicksRelationManager', function () {
    $rm = new EmailClicksRelationManager;
    $table = $rm->table(Table::make($rm));
    $names = array_map(fn ($col) => $col->getName(), $table->getColumns());

    foreach (['recipient.address', 'original_url', 'clicked_at', 'recipient.clicks_count'] as $col) {
        expect($names)->toContain($col);
    }
});

it('exposes per-recipient + per-URL columns on the SMS ClicksRelationManager', function () {
    $rm = new SmsClicksRelationManager;
    $table = $rm->table(Table::make($rm));
    $names = array_map(fn ($col) => $col->getName(), $table->getColumns());

    foreach (['recipient.phone.number', 'original_url', 'clicked_at', 'recipient.clicks_count'] as $col) {
        expect($names)->toContain($col);
    }
});

it('declares recipient + URL filters on both ClicksRelationManagers', function (string $rmClass) {
    $rm = new $rmClass;
    $table = $rm->table(Table::make($rm));
    $filterNames = array_map(fn ($filter) => $filter->getName(), $table->getFilters());

    expect($filterNames)->toContain('recipient')
        ->and($filterNames)->toContain('original_url');
})->with([
    'email' => [EmailClicksRelationManager::class],
    'sms' => [SmsClicksRelationManager::class],
]);

it('resolves a clicks() hasManyThrough relation on each campaign model', function (string $modelClass, string $relatedClass) {
    $instance = new $modelClass;
    $relation = $instance->clicks();

    expect($relation)->toBeInstanceOf(HasManyThrough::class);
    expect($relation->getRelated())->toBeInstanceOf($relatedClass);
})->with([
    'email' => [EmailCampaign::class, EmailCampaignClick::class],
    'sms' => [SmsCampaign::class, SmsCampaignClick::class],
]);

it('returns only clicks belonging to the campaign through the hasManyThrough', function () {
    $campaignA = EmailCampaign::create(['name' => 'A', 'subject' => 'A', 'body' => 'body', 'status' => 'sent']);
    $campaignB = EmailCampaign::create(['name' => 'B', 'subject' => 'B', 'body' => 'body', 'status' => 'sent']);

    $recipA = EmailCampaignRecipient::create(['email_campaign_id' => $campaignA->id, 'email' => 'alice@example.com', 'status' => 'sent']);
    $recipB = EmailCampaignRecipient::create(['email_campaign_id' => $campaignB->id, 'email' => 'bob@example.com', 'status' => 'sent']);

    EmailCampaignClick::create(['email_campaign_recipient_id' => $recipA->id, 'original_url' => 'https://a.example/x', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $recipA->id, 'original_url' => 'https://a.example/y', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $recipB->id, 'original_url' => 'https://b.example/z', 'clicked_at' => now()]);

    expect($campaignA->clicks()->count())->toBe(2)
        ->and($campaignB->clicks()->count())->toBe(1);
});

it('registers the EmailCampaign Top URLs widget as a footer widget on the View page', function () {
    $instance = (new ReflectionClass(ViewEmailCampaign::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ViewEmailCampaign::class, 'getFooterWidgets');
    $method->setAccessible(true);

    expect($method->invoke($instance))->toContain(EmailCampaignTopUrlsWidget::class);
});

it('registers the SmsCampaign Top URLs widget as a footer widget on the View page', function () {
    $instance = (new ReflectionClass(ViewSmsCampaign::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ViewSmsCampaign::class, 'getFooterWidgets');
    $method->setAccessible(true);

    expect($method->invoke($instance))->toContain(SmsCampaignTopUrlsWidget::class);
});

it('both Top URLs widgets extend TableWidget', function () {
    expect(is_subclass_of(EmailCampaignTopUrlsWidget::class, TableWidget::class))->toBeTrue();
    expect(is_subclass_of(SmsCampaignTopUrlsWidget::class, TableWidget::class))->toBeTrue();
});

it('the Top URLs widgets expose original_url, clicks_count, and unique_recipients columns', function (string $widgetClass) {
    $widget = new $widgetClass;
    $table = $widget->table(Table::make($widget));
    $names = array_map(fn ($col) => $col->getName(), $table->getColumns());

    foreach (['original_url', 'clicks_count', 'unique_recipients'] as $col) {
        expect($names)->toContain($col);
    }
})->with([
    'email' => [EmailCampaignTopUrlsWidget::class],
    'sms' => [SmsCampaignTopUrlsWidget::class],
]);

it('the EmailCampaign Top URLs widget aggregates clicks per URL for its campaign', function () {
    $campaign = EmailCampaign::create(['name' => 'X', 'subject' => 'X', 'body' => 'body', 'status' => 'sent']);
    $other = EmailCampaign::create(['name' => 'O', 'subject' => 'O', 'body' => 'body', 'status' => 'sent']);

    $r1 = EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@e.com', 'status' => 'sent']);
    $r2 = EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'b@e.com', 'status' => 'sent']);
    $rOther = EmailCampaignRecipient::create(['email_campaign_id' => $other->id, 'email' => 'c@e.com', 'status' => 'sent']);

    // /buy: 3 clicks (2 distinct recipients), /docs: 1 click, other campaign: 1 click on /buy
    EmailCampaignClick::create(['email_campaign_recipient_id' => $r1->id, 'original_url' => 'https://e.com/buy', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $r1->id, 'original_url' => 'https://e.com/buy', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $r2->id, 'original_url' => 'https://e.com/buy', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $r2->id, 'original_url' => 'https://e.com/docs', 'clicked_at' => now()]);
    EmailCampaignClick::create(['email_campaign_recipient_id' => $rOther->id, 'original_url' => 'https://e.com/buy', 'clicked_at' => now()]);

    $widget = new EmailCampaignTopUrlsWidget;
    $widget->record = $campaign;

    $rows = $widget->table(Table::make($widget))->getQuery()->orderBy('original_url')->get();

    expect($rows)->toHaveCount(2);
    $byUrl = $rows->keyBy('original_url');
    expect((int) $byUrl['https://e.com/buy']->clicks_count)->toBe(3)
        ->and((int) $byUrl['https://e.com/buy']->unique_recipients)->toBe(2)
        ->and((int) $byUrl['https://e.com/docs']->clicks_count)->toBe(1);
});

it('the SmsCampaign Top URLs widget aggregates clicks per URL for its campaign', function () {
    $campaign = SmsCampaign::create(['name' => 'S', 'body' => 'hi', 'status' => 'sent']);

    $r1 = SmsCampaignRecipient::create(['sms_campaign_id' => $campaign->id, 'phone' => '+15555550100', 'status' => 'sent']);
    $r2 = SmsCampaignRecipient::create(['sms_campaign_id' => $campaign->id, 'phone' => '+15555550101', 'status' => 'sent']);

    SmsCampaignClick::create(['sms_campaign_recipient_id' => $r1->id, 'original_url' => 'https://s.example/promo', 'clicked_at' => now()]);
    SmsCampaignClick::create(['sms_campaign_recipient_id' => $r2->id, 'original_url' => 'https://s.example/promo', 'clicked_at' => now()]);
    SmsCampaignClick::create(['sms_campaign_recipient_id' => $r1->id, 'original_url' => 'https://s.example/info', 'clicked_at' => now()]);

    $widget = new SmsCampaignTopUrlsWidget;
    $widget->record = $campaign;

    $rows = $widget->table(Table::make($widget))->getQuery()->orderBy('original_url')->get();

    expect($rows)->toHaveCount(2);
    $byUrl = $rows->keyBy('original_url');
    expect((int) $byUrl['https://s.example/promo']->clicks_count)->toBe(2)
        ->and((int) $byUrl['https://s.example/promo']->unique_recipients)->toBe(2)
        ->and((int) $byUrl['https://s.example/info']->clicks_count)->toBe(1);
});

it('the Top URLs widget returns an empty result set when the campaign has no clicks', function () {
    $campaign = EmailCampaign::create(['name' => 'Empty', 'subject' => 'Empty', 'body' => 'body', 'status' => 'sent']);
    $widget = new EmailCampaignTopUrlsWidget;
    $widget->record = $campaign;

    $rows = $widget->table(Table::make($widget))->getQuery()->get();

    expect($rows)->toHaveCount(0);
});
