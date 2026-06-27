<?php

use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\EmailTemplateResource;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\PipelineResource;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStages\PipelineStageResource;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\ProductCategoryResource;
use VentureDrake\LaravelCrmFilament\Resources\Roles\RoleResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsTemplates\SmsTemplateResource;
use VentureDrake\LaravelCrmFilament\Resources\TaxRates\TaxRateResource;

dataset('settingsResources', [
    'Pipeline' => [PipelineResource::class],
    'PipelineStage' => [PipelineStageResource::class],
    'Label' => [LabelResource::class],
    'LeadSource' => [LeadSourceResource::class],
    'TaxRate' => [TaxRateResource::class],
    'ProductCategory' => [ProductCategoryResource::class],
    'FieldGroup' => [FieldGroupResource::class],
    'Field' => [FieldResource::class],
    'Role' => [RoleResource::class],
    'EmailTemplate' => [EmailTemplateResource::class],
    'SmsTemplate' => [SmsTemplateResource::class],
    'ChatWidget' => [ChatWidgetResource::class],
]);

it('no longer declares the Settings cluster after the move to top-level Resources', function (string $resource) {
    expect($resource::getCluster())->toBeNull();
})->with('settingsResources');

it('routes TaxRate by integer id since it has no external_id column', function () {
    expect(TaxRateResource::getRecordRouteKeyName())->toBeNull();
});

it('routes other settings resources by external_id', function () {
    foreach ([PipelineResource::class, PipelineStageResource::class, LabelResource::class,
        LeadSourceResource::class, ProductCategoryResource::class,
        FieldGroupResource::class, FieldResource::class,
        EmailTemplateResource::class, SmsTemplateResource::class,
        ChatWidgetResource::class] as $r) {
        expect($r::getRecordRouteKeyName())->toBe('external_id');
    }
});

it('no longer ships the Settings cluster class', function () {
    // US-002 removed src/Clusters/Settings.php and the empty src/Clusters/ directory
    // after the 5 cluster pages were promoted to the top-level Pages namespace.
    expect(class_exists('VentureDrake\\LaravelCrmFilament\\Clusters\\Settings'))->toBeFalse();
});

it('protects Owner/Admin Spatie roles from edit/delete on RoleResource', function () {
    $owner = new Role(['name' => 'Owner']);
    $admin = new Role(['name' => 'Admin']);
    $custom = new Role(['name' => 'Custom']);

    expect(RoleResource::canEdit($owner))->toBeFalse();
    expect(RoleResource::canEdit($admin))->toBeFalse();
    expect(RoleResource::canEdit($custom))->toBeTrue();
    expect(RoleResource::canDelete($owner))->toBeFalse();
    expect(RoleResource::canDelete($admin))->toBeFalse();
    expect(RoleResource::canDelete($custom))->toBeTrue();
});
