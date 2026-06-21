<?php

use Filament\Facades\Filament;
use Filament\Panel;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FilesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Activities\ActivityResource;
use VentureDrake\LaravelCrmFilament\Resources\Activities\Pages\ListActivities;
use VentureDrake\LaravelCrmFilament\Resources\Activities\Pages\ViewActivity;
use VentureDrake\LaravelCrmFilament\Resources\Calls\CallResource;
use VentureDrake\LaravelCrmFilament\Resources\Calls\Pages\ListCalls;
use VentureDrake\LaravelCrmFilament\Resources\Calls\Pages\ViewCall;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Files\FileResource;
use VentureDrake\LaravelCrmFilament\Resources\Files\Pages\ListFiles;
use VentureDrake\LaravelCrmFilament\Resources\Files\Pages\ViewFile;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\LunchResource;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\Pages\ListLunches;
use VentureDrake\LaravelCrmFilament\Resources\Lunches\Pages\ViewLunch;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\MeetingResource;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\Pages\ListMeetings;
use VentureDrake\LaravelCrmFilament\Resources\Meetings\Pages\ViewMeeting;
use VentureDrake\LaravelCrmFilament\Resources\Notes\NoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Notes\Pages\ListNotes;
use VentureDrake\LaravelCrmFilament\Resources\Notes\Pages\ViewNote;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

dataset('standaloneActivityResources', [
    'Note' => [NoteResource::class, Note::class, ListNotes::class, ViewNote::class],
    'Call' => [CallResource::class, Call::class, ListCalls::class, ViewCall::class],
    'Meeting' => [MeetingResource::class, Meeting::class, ListMeetings::class, ViewMeeting::class],
    'Lunch' => [LunchResource::class, Lunch::class, ListLunches::class, ViewLunch::class],
    'File' => [FileResource::class, File::class, ListFiles::class, ViewFile::class],
    'Activity' => [ActivityResource::class, Activity::class, ListActivities::class, ViewActivity::class],
]);

it('binds each standalone resource to the right core model', function (string $resource, string $model): void {
    expect($resource::getModel())->toBe($model);
})->with('standaloneActivityResources');

it('registers index + view pages and no create page', function (string $resource, string $model, string $list, string $view): void {
    $pages = $resource::getPages();
    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');
    expect($pages)->not->toHaveKey('create');
    expect($pages)->not->toHaveKey('edit');
    expect($resource::canCreate())->toBeFalse();
})->with('standaloneActivityResources');

it('uses external_id for record routing', function (string $resource): void {
    expect($resource::getRecordRouteKeyName())->toBe('external_id');
})->with('standaloneActivityResources');

dataset('crmFilesRmParents', [
    'Lead' => [LeadResource::class],
    'Deal' => [DealResource::class],
    'Person' => [PersonResource::class],
    'Organization' => [OrganizationResource::class],
    'Quote' => [QuoteResource::class],
    'Order' => [OrderResource::class],
    'Invoice' => [InvoiceResource::class],
    'PurchaseOrder' => [PurchaseOrderResource::class],
    'Delivery' => [DeliveryResource::class],
]);

it('attaches CrmFilesRelationManager (subclass of FilesRelationManager) to every primary parent resource', function (string $resource): void {
    $relations = $resource::getRelations();
    expect($relations)->toContain(CrmFilesRelationManager::class);
    expect($relations)->not->toContain(FilesRelationManager::class);
})->with('crmFilesRmParents');

it('uses the polymorphic files relationship name from HasCrmActivities', function (): void {
    $rm = new ReflectionClass(FilesRelationManager::class);
    expect($rm->getStaticPropertyValue('relationship'))->toBe('files');
});

it('registers the standalone activity resources on the plugin', function (): void {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);
    $registered = collect($panel->getResources())->map(fn ($r) => is_string($r) ? $r : $r::class)->all();

    expect($registered)->toContain(NoteResource::class);
    expect($registered)->toContain(CallResource::class);
    expect($registered)->toContain(MeetingResource::class);
    expect($registered)->toContain(LunchResource::class);
    expect($registered)->toContain(FileResource::class);
    expect($registered)->toContain(ActivityResource::class);
});
