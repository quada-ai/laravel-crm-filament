<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use BackedEnum;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

class LeadKanban extends Page
{
    protected static string $resource = LeadResource::class;

    protected string $view = 'laravel-crm-filament::leads.kanban';

    protected static ?string $title = 'Lead pipeline';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    public ?int $ownerFilter = null;

    public function getOwners(): \Illuminate\Support\Collection
    {
        $userClass = config('auth.providers.users.model');
        return $userClass::query()->orderBy('name')->pluck('name', 'id');
    }

    public function getStages(): Collection
    {
        $pipelineIds = Pipeline::query()
            ->where('model', \VentureDrake\LaravelCrm\Models\Lead::class)
            ->pluck('id');

        return PipelineStage::query()
            ->whereIn('pipeline_id', $pipelineIds)
            ->orderBy('order')
            ->get();
    }

    public function getLeadsByStage(): array
    {
        $leads = Lead::query()
            ->whereNull('converted_at')
            ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
            ->whereNotNull('pipeline_stage_id')
            ->orderByDesc('updated_at')
            ->get();

        return $leads->groupBy('pipeline_stage_id')->all();
    }

    public function convertToDeal(string $externalId): void
    {
        $lead = Lead::query()->where('external_id', $externalId)->first();
        if (! $lead || $lead->converted_at) {
            return;
        }

        $payload = new \Illuminate\Support\Fluent([
            'title' => $lead->title,
            'description' => $lead->description,
            'amount' => ($lead->amount ?? 0) / 100,
            'currency' => $lead->currency,
            'user_owner_id' => $lead->user_owner_id,
        ]);

        app(\VentureDrake\LaravelCrm\Services\DealService::class)->create(
            $payload,
            $lead->person,
            $lead->organization,
        );

        $lead->forceFill(['converted_at' => now()])->save();
    }

    public function moveLead(string $externalId, ?int $stageId): void
    {
        $lead = Lead::query()->where('external_id', $externalId)->first();
        if (! $lead) {
            return;
        }

        $lead->pipeline_stage_id = $stageId;
        if ($stageId) {
            $stage = PipelineStage::find($stageId);
            $lead->pipeline_id = $stage?->pipeline_id;
        }
        $lead->save();
    }
    public function getStageTotal(int $stageId, array $byStage): float
    {
        $rows = $byStage[$stageId] ?? collect();
        $sum = 0;
        foreach ($rows as $row) {
            $sum += (int) ($row->amount ?? 0);
        }
        return $sum / 100;
    }
}
