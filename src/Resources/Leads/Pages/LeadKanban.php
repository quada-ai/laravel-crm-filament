<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use BackedEnum;
use Filament\Actions;
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

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-view-columns';

    public ?int $ownerFilter = null;

    public ?string $statusFilter = 'all';

    protected function getHeaderActions(): array
    {
        return [
            ...LeadResource::listKanbanToggleActions('kanban'),
            Actions\CreateAction::make()
                ->url(LeadResource::getUrl('create')),
        ];
    }

    public function getOwners(): Collection
    {
        $userClass = config('auth.providers.users.model');

        return $userClass::query()->orderBy('name')->pluck('name', 'id');
    }

    public function getStages(): Collection
    {
        $pipelineIds = Pipeline::query()
            ->where('model', Lead::class)
            ->orWhereNull('model')
            ->pluck('id');

        $query = PipelineStage::query();
        if ($pipelineIds->isNotEmpty()) {
            $query->whereIn('pipeline_id', $pipelineIds);
        }

        return $query->orderBy('order')->get();
    }

    public function getLeadsByStage(): array
    {
        $leads = Lead::query()
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereNull('converted_at'))
            ->when($this->statusFilter === 'converted', fn ($q) => $q->whereNotNull('converted_at'))
            ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
            ->orderByDesc('updated_at')
            ->get();

        $defaultStage = $this->getStages()->first();

        $grouped = [];
        foreach ($leads as $lead) {
            $stageId = $lead->pipeline_stage_id ?: ($defaultStage?->id ?? 0);
            if ($stageId > 0) {
                if (! isset($grouped[$stageId])) {
                    $grouped[$stageId] = collect();
                }
                $grouped[$stageId]->push($lead);
            }
        }

        return $grouped;
    }

    public function convertToDeal(string $externalId): void
    {
        $lead = Lead::query()->where('external_id', $externalId)->first();
        if (! $lead) {
            return;
        }

        LeadResource::doConvertToDeal($lead);
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
