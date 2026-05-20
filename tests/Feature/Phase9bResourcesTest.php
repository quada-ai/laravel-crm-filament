<?php

use Filament\Facades\Filament;
use Filament\Panel;
use VentureDrake\LaravelCrm\Models\LeadStatus;
use VentureDrake\LaravelCrm\Models\PipelineStageProbability;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Pages\Updates;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\CrmTeamResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\CreateCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\EditCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\ListCrmTeams;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\ViewCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\RelationManagers\TeamMembersRelationManager;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadStatuses\LeadStatusResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStageProbabilities\PipelineStageProbabilityResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

it('binds LeadStatusResource to the LeadStatus model and lives in the Settings cluster', function () {
    expect(LeadStatusResource::getModel())->toBe(LeadStatus::class);
    expect(LeadStatusResource::getCluster())->toBe(Settings::class);
    expect(LeadStatusResource::getRecordRouteKeyName())->toBe('external_id');
});

it('exposes list+create+edit pages on LeadStatusResource', function () {
    expect(array_keys(LeadStatusResource::getPages()))->toEqual(['index', 'create', 'edit']);
});

it('binds PipelineStageProbabilityResource to the model and lives in the Settings cluster', function () {
    expect(PipelineStageProbabilityResource::getModel())->toBe(PipelineStageProbability::class);
    expect(PipelineStageProbabilityResource::getCluster())->toBe(Settings::class);
    expect(PipelineStageProbabilityResource::getRecordRouteKeyName())->toBe('external_id');
});

it('exposes list+create+edit pages on PipelineStageProbabilityResource', function () {
    expect(array_keys(PipelineStageProbabilityResource::getPages()))->toEqual(['index', 'create', 'edit']);
});

it('wires a lead_status_id Select onto the Lead form', function () {
    $source = file_get_contents((new ReflectionClass(LeadResource::class))->getFileName());
    expect($source)->toContain("Forms\\Components\\Select::make('lead_status_id')");
    expect($source)->toContain('LeadStatus::query()');
});

it('wires a pipeline_stage_probability_id Select onto the PipelineStage form', function () {
    $source = file_get_contents((new ReflectionClass(PipelineStageResource::class))->getFileName());
    expect($source)->toContain("Forms\\Components\\Select::make('pipeline_stage_probability_id')");
    expect($source)->toContain('PipelineStageProbability::query()');
});

it('binds CrmTeamResource to the Team model and lives in the Settings cluster', function () {
    expect(CrmTeamResource::getModel())->toBe(Team::class);
    expect(CrmTeamResource::getCluster())->toBe(Settings::class);
    expect(CrmTeamResource::getSlug())->toBe('crm-teams');
});

it('exposes CRUD pages on CrmTeamResource at the expected slug', function () {
    $pages = CrmTeamResource::getPages();
    expect(array_keys($pages))->toEqual(['index', 'create', 'view', 'edit']);
});

it('routes CrmTeam page classes back to CrmTeamResource', function () {
    foreach ([CreateCrmTeam::class, EditCrmTeam::class, ListCrmTeams::class, ViewCrmTeam::class] as $page) {
        $reflection = new ReflectionProperty($page, 'resource');
        $reflection->setAccessible(true);
        expect($reflection->getValue())->toBe(CrmTeamResource::class);
    }
});

it('attaches the TeamMembers relation manager to CrmTeamResource', function () {
    expect(CrmTeamResource::getRelations())->toContain(TeamMembersRelationManager::class);
});

it('targets the users relationship on the Team model from the TeamMembers RM', function () {
    $reflection = new ReflectionProperty(TeamMembersRelationManager::class, 'relationship');
    $reflection->setAccessible(true);
    expect($reflection->getValue())->toBe('users');
});

it('declares the Updates settings page in the Settings cluster at /updates', function () {
    expect(Updates::getCluster())->toBe(Settings::class);
    expect(Updates::getSlug())->toBe('updates');
});

it('queues laravelcrm:update from the Updates page Check action', function () {
    $source = file_get_contents((new ReflectionClass(Updates::class))->getFileName());
    expect($source)->toContain('Artisan::queue');
    expect($source)->toContain("'laravelcrm:update'");
    expect($source)->toContain("'version_latest'");
});

it('registers all four new surfaces on the panel', function () {
    $plugin = LaravelCrmPlugin::make();
    $panel = Filament::getPanel('admin', false) ?? Panel::make()->id('admin')->default();
    $plugin->register($panel);

    foreach ([
        LeadStatusResource::class,
        PipelineStageProbabilityResource::class,
        CrmTeamResource::class,
    ] as $resource) {
        expect($panel->getResources())->toContain($resource);
    }
});
