<?php

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Jobs\SendEmailCampaignRecipient;
use VentureDrake\LaravelCrm\Jobs\SendSmsCampaignRecipient;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers\RecipientsRelationManager as EmailRecipientsRm;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers\RecipientsRelationManager as SmsRecipientsRm;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

dataset('primaryResources', [
    'lead' => [LeadResource::class],
    'deal' => [DealResource::class],
    'quote' => [QuoteResource::class],
    'order' => [OrderResource::class],
    'invoice' => [InvoiceResource::class],
]);

dataset('stagedResources', [
    'lead' => [LeadResource::class],
    'deal' => [DealResource::class],
]);

dataset('unstagedResources', [
    'quote' => [QuoteResource::class],
    'order' => [OrderResource::class],
    'invoice' => [InvoiceResource::class],
]);

it('uses the HasPrimaryBulkActions trait on every primary resource', function (string $resource) {
    expect(class_uses_recursive($resource))->toContain(HasPrimaryBulkActions::class);
})->with('primaryResources');

it('returns a BulkActionGroup with assignOwner, applyLabels, archive on every primary resource', function (string $resource) {
    $withStage = in_array($resource, [LeadResource::class, DealResource::class], true);
    $group = $resource::primaryBulkActionGroup(withPipelineStage: $withStage);

    expect($group)->toBeInstanceOf(BulkActionGroup::class);

    $names = array_map(fn ($a) => $a->getName(), $group->getActions());
    expect($names)->toContain('assignOwner');
    expect($names)->toContain('applyLabels');
    expect($names)->toContain('archive');
})->with('primaryResources');

it('exposes changePipelineStage on Lead and Deal only', function (string $resource) {
    $group = $resource::primaryBulkActionGroup(withPipelineStage: true);
    $names = array_map(fn ($a) => $a->getName(), $group->getActions());

    expect($names)->toContain('changePipelineStage');
})->with('stagedResources');

it('hides changePipelineStage on Quote/Order/Invoice (withPipelineStage: false)', function (string $resource) {
    $group = $resource::primaryBulkActionGroup();
    $names = array_map(fn ($a) => $a->getName(), $group->getActions());

    expect($names)->not->toContain('changePipelineStage');
})->with('unstagedResources');

it('wires static::primaryBulkActionGroup() into every primary resource table()', function (string $resource) {
    $source = file_get_contents((new ReflectionClass($resource))->getFileName());
    expect($source)->toContain('static::primaryBulkActionGroup');
})->with('primaryResources');

it('archive action uses soft-delete ($record->delete()), not forceDelete', function () {
    $source = file_get_contents((new ReflectionClass(HasPrimaryBulkActions::class))->getFileName());
    expect($source)->toContain('$record->delete()');
    expect($source)->not->toContain('forceDelete');
});

it('archive action requires confirmation', function () {
    $action = LeadResource::archiveBulkAction();
    expect($action)->toBeInstanceOf(BulkAction::class);
    expect($action->isConfirmationRequired())->toBeTrue();
});

it('archive bulk action soft-deletes selected records', function () {
    RoleSeeder::seed();
    $admin = User::create([
        'name' => 'Admin ' . uniqid(),
        'email' => 'admin-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Archive me',
    ]);

    $closure = LeadResource::archiveBulkAction()->getActionFunction();
    $closure(new Collection([$lead]));

    $lead->refresh();
    expect($lead->trashed())->toBeTrue();
});

it('assignOwner bulk action writes user_owner_id on selected records', function () {
    RoleSeeder::seed();
    $admin = User::create([
        'name' => 'Admin ' . uniqid(),
        'email' => 'admin-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $admin->assignRole('Admin');
    $owner = User::create([
        'name' => 'New Owner',
        'email' => 'newowner-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->actingAs($admin);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Reassign me',
        'user_owner_id' => $admin->id,
    ]);

    $closure = LeadResource::assignOwnerBulkAction()->getActionFunction();
    $closure(new Collection([$lead]), ['user_owner_id' => $owner->id]);

    $lead->refresh();
    expect((int) $lead->user_owner_id)->toBe($owner->id);
});

it('changePipelineStage bulk action writes pipeline_stage_id on selected records', function () {
    RoleSeeder::seed();
    $admin = User::create([
        'name' => 'Admin ' . uniqid(),
        'email' => 'admin-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $pipeline = Pipeline::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Default',
        'model' => Lead::class,
        'order' => 1,
    ]);

    $stage = PipelineStage::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Qualified',
        'pipeline_id' => $pipeline->id,
        'order' => 2,
    ]);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Move stage',
    ]);

    $closure = LeadResource::changePipelineStageBulkAction()->getActionFunction();
    $closure(new Collection([$lead]), ['pipeline_stage_id' => $stage->id]);

    $lead->refresh();
    expect((int) $lead->pipeline_stage_id)->toBe($stage->id);
});

it('applyLabels bulk action attaches labels to selected records without detaching existing ones', function () {
    RoleSeeder::seed();
    $admin = User::create([
        'name' => 'Admin ' . uniqid(),
        'email' => 'admin-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $existing = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Existing',
        'hex' => '000000',
    ]);
    $extra = Label::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Extra',
        'hex' => 'ffffff',
    ]);

    $lead = Lead::create([
        'external_id' => (string) Str::uuid(),
        'title' => 'Tag me',
    ]);
    $lead->labels()->attach($existing->id);

    $closure = LeadResource::applyLabelsBulkAction()->getActionFunction();
    $closure(new Collection([$lead]), ['label_ids' => [$extra->id]]);

    $attachedIds = $lead->labels()->pluck('crm_labels.id')->all();
    expect($attachedIds)->toContain($existing->id);
    expect($attachedIds)->toContain($extra->id);
});

it('registers a resendToFailed bulk action on the Email RecipientsRelationManager source', function () {
    $source = file_get_contents((new ReflectionClass(EmailRecipientsRm::class))->getFileName());
    expect($source)->toContain("BulkAction::make('resendToFailed')");
    expect($source)->toContain('SendEmailCampaignRecipient::dispatch');
});

it('registers a resendToFailed bulk action on the SMS RecipientsRelationManager source', function () {
    $source = file_get_contents((new ReflectionClass(SmsRecipientsRm::class))->getFileName());
    expect($source)->toContain("BulkAction::make('resendToFailed')");
    expect($source)->toContain('SendSmsCampaignRecipient::dispatch');
});

function bulkActionsFromRm(object $rm): array
{
    $table = $rm->table(Table::make($rm));
    $bulk = [];
    foreach ($table->getToolbarActions() as $node) {
        if ($node instanceof BulkActionGroup) {
            foreach ($node->getActions() as $action) {
                $bulk[$action->getName()] = $action;
            }
        }
    }

    return $bulk;
}

it('exposes resendToFailed as a BulkAction on the Email RecipientsRelationManager table', function () {
    $bulk = bulkActionsFromRm(new EmailRecipientsRm);
    expect($bulk)->toHaveKey('resendToFailed');
    expect($bulk['resendToFailed']->isConfirmationRequired())->toBeTrue();
});

it('exposes resendToFailed as a BulkAction on the SMS RecipientsRelationManager table', function () {
    $bulk = bulkActionsFromRm(new SmsRecipientsRm);
    expect($bulk)->toHaveKey('resendToFailed');
    expect($bulk['resendToFailed']->isConfirmationRequired())->toBeTrue();
});

it('email resendToFailed re-queues SendEmailCampaignRecipient for failed/bounced recipients only', function () {
    Bus::fake([SendEmailCampaignRecipient::class]);

    $campaign = EmailCampaign::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Test',
        'subject' => 'Test',
        'body' => 'Test',
    ]);

    $failed = EmailCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'email_campaign_id' => $campaign->id,
        'email' => 'failed@example.com',
        'status' => 'failed',
    ]);
    $bounced = EmailCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'email_campaign_id' => $campaign->id,
        'email' => 'bounced@example.com',
        'status' => 'bounced',
    ]);
    $sent = EmailCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'email_campaign_id' => $campaign->id,
        'email' => 'sent@example.com',
        'status' => 'sent',
    ]);

    $bulk = bulkActionsFromRm(new EmailRecipientsRm);
    $closure = $bulk['resendToFailed']->getActionFunction();
    $closure(new Collection([$failed, $bounced, $sent]));

    Bus::assertDispatchedTimes(SendEmailCampaignRecipient::class, 2);

    $failed->refresh();
    $bounced->refresh();
    $sent->refresh();
    expect($failed->status)->toBe('pending');
    expect($bounced->status)->toBe('pending');
    expect($sent->status)->toBe('sent');
});

it('sms resendToFailed re-queues SendSmsCampaignRecipient for failed/bounced recipients only', function () {
    Bus::fake([SendSmsCampaignRecipient::class]);

    $campaign = SmsCampaign::create([
        'external_id' => (string) Str::uuid(),
        'name' => 'Test',
        'body' => 'Test',
    ]);

    $failed = SmsCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'sms_campaign_id' => $campaign->id,
        'phone' => '+15551111',
        'status' => 'failed',
    ]);
    $bounced = SmsCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'sms_campaign_id' => $campaign->id,
        'phone' => '+15552222',
        'status' => 'bounced',
    ]);
    $sent = SmsCampaignRecipient::create([
        'external_id' => (string) Str::uuid(),
        'sms_campaign_id' => $campaign->id,
        'phone' => '+15553333',
        'status' => 'sent',
    ]);

    $bulk = bulkActionsFromRm(new SmsRecipientsRm);
    $closure = $bulk['resendToFailed']->getActionFunction();
    $closure(new Collection([$failed, $bounced, $sent]));

    Bus::assertDispatchedTimes(SendSmsCampaignRecipient::class, 2);

    $failed->refresh();
    $bounced->refresh();
    $sent->refresh();
    expect($failed->status)->toBe('pending');
    expect($bounced->status)->toBe('pending');
    expect($sent->status)->toBe('sent');
});
