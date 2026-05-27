<?php

use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\ListEmailCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\ListSmsCampaigns;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages\ListTasks;

dataset('listPagesWithExpectedTabs', [
    'Tasks' => [ListTasks::class, ['all', 'open', 'today', 'overdue', 'completed']],
    'EmailCampaigns' => [ListEmailCampaigns::class, ['all', 'draft', 'scheduled', 'sending', 'sent', 'failed']],
    'SmsCampaigns' => [ListSmsCampaigns::class, ['all', 'draft', 'scheduled', 'sending', 'sent', 'failed']],
]);

it('exposes the expected status tabs on each list page', function (string $page, array $expectedTabs) {
    $instance = new $page;
    $tabs = $instance->getTabs();

    expect(array_keys($tabs))->toBe($expectedTabs);
})->with('listPagesWithExpectedTabs');
