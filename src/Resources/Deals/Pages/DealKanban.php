<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use BackedEnum;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

class DealKanban extends Page
{
    protected static string $resource = DealResource::class;

    protected string $view = 'laravel-crm-filament::deals.kanban';

    protected static ?string $title = 'Deal pipeline';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-view-columns';

    public ?int $ownerFilter = null;

    public ?string $statusFilter = 'all';

    protected function getHeaderActions(): array
    {
        return [
            ...DealResource::listKanbanToggleActions('kanban'),
            Actions\CreateAction::make()
                ->url(DealResource::getUrl('create')),
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
            ->whereIn('model', [Deal::class, 'Deal', 'deal'])
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

    public function getDealsByStage(): array
    {
        $stages = $this->getStages();
        $stageIds = $stages->pluck('id')->all();
        $defaultStageId = $stages->first()?->id;

        $query = Deal::query();

        if ($this->statusFilter === 'open') {
            $query->whereNull('closed_at');
        } elseif ($this->statusFilter === 'won') {
            $query->where(fn ($q) => $q->whereIn('closed_status', ['won', 'Won'])->orWhere(fn ($q2) => $q2->whereNotNull('closed_at')->where('closed_status', '!=', 'lost')));
        } elseif ($this->statusFilter === 'lost') {
            $query->where(fn ($q) => $q->whereIn('closed_status', ['lost', 'Lost'])->orWhere(fn ($q2) => $q2->whereNotNull('closed_at')->where('closed_status', 'lost')));
        }

        if ($this->ownerFilter) {
            $query->where('user_owner_id', $this->ownerFilter);
        }

        $deals = $query->orderByDesc('updated_at')->get();

        $grouped = [];
        foreach ($deals as $deal) {
            $stageId = $deal->pipeline_stage_id;
            if (! $stageId || ! in_array($stageId, $stageIds)) {
                $stageId = $defaultStageId;
            }

            if ($stageId) {
                if (! isset($grouped[$stageId])) {
                    $grouped[$stageId] = collect();
                }
                $grouped[$stageId]->push($deal);
            }
        }

        return $grouped;
    }

    public function markWon(string $externalId): void
    {
        $this->closeDeal($externalId, true);
    }

    public function markLost(string $externalId): void
    {
        $this->closeDeal($externalId, false);
    }

    public function reopen(string $externalId): void
    {
        $deal = Deal::query()->where('external_id', $externalId)->first();
        if (! $deal) {
            return;
        }
        $deal->forceFill([
            'closed_at' => null,
            'closed_status' => null,
        ]);
        if (Schema::hasColumn($deal->getTable(), 'won')) {
            $deal->won = null;
        }
        $deal->save();
    }

    protected function closeDeal(string $externalId, bool $won): void
    {
        $deal = Deal::query()->where('external_id', $externalId)->first();
        if (! $deal) {
            return;
        }
        $deal->forceFill([
            'closed_at' => now(),
            'closed_status' => $won ? 'won' : 'lost',
        ]);
        if (Schema::hasColumn($deal->getTable(), 'won')) {
            $deal->won = $won;
        }
        $deal->save();
    }

    public function moveDeal(string $externalId, ?int $stageId): void
    {
        $deal = Deal::query()->where('external_id', $externalId)->first();
        if (! $deal) {
            return;
        }
        $deal->pipeline_stage_id = $stageId;
        if ($stageId) {
            $stage = PipelineStage::find($stageId);
            $deal->pipeline_id = $stage?->pipeline_id;
        }
        $deal->save();
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
