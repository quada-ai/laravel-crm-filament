<?php

use Illuminate\Contracts\Auth\Access\Gate;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Resources\Activities\ActivityResource;
use VentureDrake\LaravelCrmFilament\Resources\Calls\CallResource;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;
use VentureDrake\LaravelCrmFilament\Resources\Customers\CustomerResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\Files\FileResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\LunchResource;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\MeetingResource;
use VentureDrake\LaravelCrmFilament\Resources\Notes\NoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Policy User ' . uniqid(),
        'email' => 'policy' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
});

/**
 * The primary "editable" resources — each has a Filament create page
 * AND a core CRM Policy with create/update/delete methods.
 */
dataset('editable_resources', [
    'lead' => [LeadResource::class, Lead::class],
    'deal' => [DealResource::class, Deal::class],
    'quote' => [QuoteResource::class, Quote::class],
    'order' => [OrderResource::class, Order::class],
    'invoice' => [InvoiceResource::class, Invoice::class],
    'delivery' => [DeliveryResource::class, Delivery::class],
    'purchase-order' => [PurchaseOrderResource::class, PurchaseOrder::class],
    'person' => [PersonResource::class, Person::class],
    'organization' => [OrganizationResource::class, Organization::class],
    'customer' => [CustomerResource::class, Customer::class],
    'product' => [ProductResource::class, Product::class],
    'task' => [TaskResource::class, Task::class],
    'email-campaign' => [EmailCampaignResource::class, EmailCampaign::class],
    'sms-campaign' => [SmsCampaignResource::class, SmsCampaign::class],
]);

/**
 * "Read-only standalone" resources — Activity/Note/Call/Meeting/
 * Lunch/File aggregate cross-entity timelines (US-003). Filament
 * never lets the panel user create these — canCreate() returns
 * false unconditionally — so the policy gate test for them is
 * structural (canCreate must always be false, regardless of role).
 */
dataset('readonly_resources', [
    'activity' => [ActivityResource::class, Activity::class],
    'note' => [NoteResource::class, Note::class],
    'call' => [CallResource::class, Call::class],
    'meeting' => [MeetingResource::class, Meeting::class],
    'lunch' => [LunchResource::class, Lunch::class],
    'file' => [FileResource::class, File::class],
]);

dataset('crm_roles', [
    'Owner' => ['Owner'],
    'Admin' => ['Admin'],
    'Manager' => ['Manager'],
    'Employee' => ['Employee'],
]);

it('grants create/edit/delete to a user holding the role', function (string $resource, string $model, string $roleName) {
    $this->user->assignRole($roleName);
    $this->actingAs($this->user);

    $record = new $model;

    expect($resource::canCreate())->toBeTrue("$roleName should be able to create $resource");
    expect($resource::canEdit($record))->toBeTrue("$roleName should be able to edit $resource");
    expect($resource::canDelete($record))->toBeTrue("$roleName should be able to delete $resource");
})->with('editable_resources')->with('crm_roles');

it('denies create on a user without any role', function (string $resource, string $model) {
    $this->actingAs($this->user);

    expect($resource::canCreate())->toBeFalse("unauth user should not be able to create $resource");
})->with('editable_resources');

it('denies edit and delete to a user without any role', function (string $resource, string $model) {
    $this->actingAs($this->user);

    $record = new $model;

    expect($resource::canEdit($record))->toBeFalse("unauth user should not be able to edit $resource");
    expect($resource::canDelete($record))->toBeFalse("unauth user should not be able to delete $resource");
})->with('editable_resources');

it('keeps canCreate locked off on the read-only standalone resources regardless of role', function (string $resource, string $model, string $roleName) {
    $this->user->assignRole($roleName);
    $this->actingAs($this->user);

    expect($resource::canCreate())->toBeFalse("$resource is read-only — canCreate() must always be false");
})->with('readonly_resources')->with('crm_roles');

it('exposes ChatConversation through its policy view/reply gates', function () {
    $this->user->assignRole('Owner');
    $this->actingAs($this->user);

    expect(ChatConversationResource::canViewAny())->toBeTrue();
});

it('registers core CRM policies in the gate so Filament can dispatch authorization', function () {
    $gate = app(Gate::class);

    foreach ([
        Lead::class,
        Deal::class,
        Quote::class,
        Order::class,
        Invoice::class,
        Delivery::class,
        PurchaseOrder::class,
        Person::class,
        Organization::class,
        Customer::class,
        Product::class,
        Task::class,
        Note::class,
        Call::class,
        Meeting::class,
        Lunch::class,
        File::class,
        EmailCampaign::class,
        SmsCampaign::class,
        ChatConversation::class,
    ] as $model) {
        expect($gate->getPolicyFor($model))
            ->not->toBeNull("Expected a registered policy for $model");
    }
});
