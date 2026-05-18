<?php

use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\ChatWidgetResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\EmailTemplates\EmailTemplateResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\PipelineResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductCategories\ProductCategoryResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles\RoleResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\SmsTemplateResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\TaxRateResource;

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

it('declares the Settings cluster on every cluster resource', function (string $resource) {
    expect($resource::getCluster())->toBe(Settings::class);
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

it('declares the Settings cluster nav icon', function () {
    expect(Settings::getNavigationIcon())->toBe('heroicon-o-cog-6-tooth');
});

it('protects Owner/Admin Spatie roles from edit/delete on RoleResource', function () {
    $owner = new \Spatie\Permission\Models\Role(['name' => 'Owner']);
    $admin = new \Spatie\Permission\Models\Role(['name' => 'Admin']);
    $custom = new \Spatie\Permission\Models\Role(['name' => 'Custom']);

    expect(RoleResource::canEdit($owner))->toBeFalse();
    expect(RoleResource::canEdit($admin))->toBeFalse();
    expect(RoleResource::canEdit($custom))->toBeTrue();
    expect(RoleResource::canDelete($owner))->toBeFalse();
    expect(RoleResource::canDelete($admin))->toBeFalse();
    expect(RoleResource::canDelete($custom))->toBeTrue();
});
