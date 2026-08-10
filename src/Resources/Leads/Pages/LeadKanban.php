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
        return collect(\VentureDrake\LaravelCrmFilament\Support\UserOptions::get());
    }

    public function getStages(): Collection
    {
        $pipelineIds = Pipeline::query()
            ->whereIn('model', [Lead::class, 'Lead', 'lead'])
            ->orWhereNull('model')
            ->pluck('id');

        $stages = PipelineStage::query()
            ->when($pipelineIds->isNotEmpty(), fn ($q) => $q->whereIn('pipeline_id', $pipelineIds))
            ->orderBy('order')
            ->get();

        if ($stages->isEmpty()) {
            $stages = PipelineStage::query()->orderBy('order')->get();
        }

        return $stages;
    }

    public function getLeadsByStage(): array
    {
        $stages = $this->getStages();
        $stageIds = $stages->pluck('id')->all();
        $defaultStageId = $stages->first()?->id;

        $leads = Lead::query()
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereNull('converted_at'))
            ->when($this->statusFilter === 'converted', fn ($q) => $q->whereNotNull('converted_at'))
            ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
            ->orderByDesc('updated_at')
            ->get();

        $grouped = [];
        foreach ($leads as $lead) {
            $stageId = $lead->pipeline_stage_id;
            if (! $stageId || ! in_array($stageId, $stageIds)) {
                $stageId = $defaultStageId;
            }

            if ($stageId) {
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
