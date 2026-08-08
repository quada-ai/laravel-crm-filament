<?php

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\DealKanban;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\Pages\LeadKanban;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\QuoteKanban;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

beforeEach(function () {
    RoleSeeder::seed();

    $this->user = User::create([
        'name' => 'Kanban Tester',
        'email' => 'kanban-tester-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);
});

it('scopes stage options in Lead, Deal, and Quote resource table filters to related pipelines', function () {
    $leadPipeline = Pipeline::create(['name' => 'Lead Pipeline', 'model' => Lead::class]);
    $leadStage = PipelineStage::create(['name' => 'Lead Stage X', 'pipeline_id' => $leadPipeline->id, 'order' => 1]);

    $dealPipeline = Pipeline::create(['name' => 'Deal Pipeline', 'model' => Deal::class]);
    $dealStage = PipelineStage::create(['name' => 'Deal Stage Y', 'pipeline_id' => $dealPipeline->id, 'order' => 1]);

    $quotePipeline = Pipeline::create(['name' => 'Quote Pipeline', 'model' => Quote::class]);
    $quoteStage = PipelineStage::create(['name' => 'Quote Stage Z', 'pipeline_id' => $quotePipeline->id, 'order' => 1]);

    // Test LeadResource stage filter options
    $leadStageFilter = collect(LeadResource::table(app(\Filament\Tables\Table::class))->getFilters())
        ->firstWhere(fn ($f) => $f->getName() === 'pipeline_stage_id');
    $getMock = fn ($key) => null;
    $livewireMock = (object) ['tableFilters' => []];
    $leadOptions = call_user_func($leadStageFilter->getOptions(...), $getMock, $livewireMock);
    expect($leadOptions)->toHaveKey($leadStage->id);
    expect($leadOptions)->not->toHaveKey($dealStage->id);
    expect($leadOptions)->not->toHaveKey($quoteStage->id);

    // Test DealResource stage filter options
    $dealStageFilter = collect(DealResource::table(app(\Filament\Tables\Table::class))->getFilters())
        ->firstWhere(fn ($f) => $f->getName() === 'pipeline_stage_id');
    $dealOptions = call_user_func($dealStageFilter->getOptions(...), $getMock, $livewireMock);
    expect($dealOptions)->toHaveKey($dealStage->id);
    expect($dealOptions)->not->toHaveKey($leadStage->id);
    expect($dealOptions)->not->toHaveKey($quoteStage->id);

    // Test QuoteResource stage filter options
    $quoteStageFilter = collect(QuoteResource::table(app(\Filament\Tables\Table::class))->getFilters())
        ->firstWhere(fn ($f) => $f->getName() === 'pipeline_stage_id');
    $quoteOptions = call_user_func($quoteStageFilter->getOptions(...), $getMock, $livewireMock);
    expect($quoteOptions)->toHaveKey($quoteStage->id);
    expect($quoteOptions)->not->toHaveKey($leadStage->id);
    expect($quoteOptions)->not->toHaveKey($dealStage->id);
});

it('filters stage options dynamically when pipeline_id is selected', function () {
    $pipelineA = Pipeline::create(['name' => 'Custom Lead Pipe A', 'model' => Lead::class]);
    $stageA = PipelineStage::create(['name' => 'Stage A', 'pipeline_id' => $pipelineA->id, 'order' => 1]);

    $pipelineB = Pipeline::create(['name' => 'Custom Lead Pipe B', 'model' => Lead::class]);
    $stageB = PipelineStage::create(['name' => 'Stage B', 'pipeline_id' => $pipelineB->id, 'order' => 1]);

    $leadStageFilter = collect(LeadResource::table(app(\Filament\Tables\Table::class))->getFilters())
        ->firstWhere(fn ($f) => $f->getName() === 'pipeline_stage_id');

    $getMock = fn ($key) => $key === 'pipeline_id' ? $pipelineA->id : null;
    $livewireMock = (object) ['tableFilters' => []];
    $options = call_user_func($leadStageFilter->getOptions(...), $getMock, $livewireMock);

    expect($options)->toHaveKey($stageA->id);
    expect($options)->not->toHaveKey($stageB->id);
});

it('includes closed won and closed lost deals in DealKanban by default (statusFilter=all)', function () {
    $pipeline = Pipeline::create(['name' => 'Deals Pipe', 'model' => Deal::class]);
    $stage = PipelineStage::create(['name' => 'Qualified', 'pipeline_id' => $pipeline->id, 'order' => 1]);

    $openDeal = Deal::create(['title' => 'Open Deal', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id]);
    $wonDeal = Deal::create(['title' => 'Won Deal', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'closed_at' => now(), 'closed_status' => 'won']);
    $lostDeal = Deal::create(['title' => 'Lost Deal', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'closed_at' => now(), 'closed_status' => 'lost']);

    $kanban = new DealKanban();
    $dealsByStage = $kanban->getDealsByStage();
    $stageDeals = $dealsByStage[$stage->id] ?? collect();

    $ids = $stageDeals->pluck('id')->all();
    expect($ids)->toContain($openDeal->id, $wonDeal->id, $lostDeal->id);

    // Test filtering by 'open'
    $kanban->statusFilter = 'open';
    $openDeals = ($kanban->getDealsByStage()[$stage->id] ?? collect())->pluck('id')->all();
    expect($openDeals)->toContain($openDeal->id)->not->toContain($wonDeal->id, $lostDeal->id);

    // Test filtering by 'won'
    $kanban->statusFilter = 'won';
    $wonDeals = ($kanban->getDealsByStage()[$stage->id] ?? collect())->pluck('id')->all();
    expect($wonDeals)->toContain($wonDeal->id)->not->toContain($openDeal->id, $lostDeal->id);

    // Test filtering by 'lost'
    $kanban->statusFilter = 'lost';
    $lostDeals = ($kanban->getDealsByStage()[$stage->id] ?? collect())->pluck('id')->all();
    expect($lostDeals)->toContain($lostDeal->id)->not->toContain($openDeal->id, $wonDeal->id);
});

it('can reopen a closed deal in DealKanban', function () {
    $pipeline = Pipeline::create(['name' => 'Deals Pipe 2', 'model' => Deal::class]);
    $stage = PipelineStage::create(['name' => 'In Progress', 'pipeline_id' => $pipeline->id, 'order' => 1]);

    $wonDeal = Deal::create([
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'title' => 'Won Deal To Reopen',
        'pipeline_id' => $pipeline->id,
        'pipeline_stage_id' => $stage->id,
        'closed_at' => now(),
        'closed_status' => 'won',
    ]);

    $kanban = new DealKanban();
    $kanban->reopen($wonDeal->external_id);

    $fresh = $wonDeal->fresh();
    expect($fresh->closed_at)->toBeNull();
    expect($fresh->closed_status)->toBeNull();
});

it('includes converted leads in LeadKanban when statusFilter=all', function () {
    $pipeline = Pipeline::create(['name' => 'Leads Pipe', 'model' => Lead::class]);
    $stage = PipelineStage::create(['name' => 'New Lead', 'pipeline_id' => $pipeline->id, 'order' => 1]);

    $openLead = Lead::create(['title' => 'Open Lead', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id]);
    $convertedLead = Lead::create(['title' => 'Converted Lead', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'converted_at' => now()]);

    $kanban = new LeadKanban();
    $leadsByStage = $kanban->getLeadsByStage();
    $stageLeads = $leadsByStage[$stage->id] ?? collect();

    $ids = $stageLeads->pluck('id')->all();
    expect($ids)->toContain($openLead->id, $convertedLead->id);
});

it('includes accepted and rejected quotes in QuoteKanban when statusFilter=all', function () {
    $pipeline = Pipeline::create(['name' => 'Quotes Pipe', 'model' => Quote::class]);
    $stage = PipelineStage::create(['name' => 'Draft Quote', 'pipeline_id' => $pipeline->id, 'order' => 1]);

    $openQuote = Quote::create(['title' => 'Open Quote', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id]);
    $acceptedQuote = Quote::create(['title' => 'Accepted Quote', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'accepted_at' => now()]);
    $rejectedQuote = Quote::create(['title' => 'Rejected Quote', 'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'rejected_at' => now()]);

    $kanban = new QuoteKanban();
    $quotesByStage = $kanban->getQuotesByStage();
    $stageQuotes = $quotesByStage[$stage->id] ?? collect();

    $ids = $stageQuotes->pluck('id')->all();
    expect($ids)->toContain($openQuote->id, $acceptedQuote->id, $rejectedQuote->id);

    $kanban->reopen($acceptedQuote->external_id);
    expect($acceptedQuote->fresh()->accepted_at)->toBeNull();
});
