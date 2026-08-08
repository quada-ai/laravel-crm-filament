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

        $wonStage = $stages->first(fn ($s) => str_contains(strtolower($s->name), 'won'));
        $lostStage = $stages->first(fn ($s) => str_contains(strtolower($s->name), 'lost'));

        $rawQuery = Deal::query();
        $resQuery = DealResource::getEloquentQuery();

        if ($this->statusFilter === 'open') {
            $filterOpen = function ($q) use ($wonStage, $lostStage) {
                $q->whereNull('closed_status')->whereNull('closed_at');
                if ($wonStage) {
                    $q->where('pipeline_stage_id', '!=', $wonStage->id);
                }
                if ($lostStage) {
                    $q->where('pipeline_stage_id', '!=', $lostStage->id);
                }
            };
            $rawQuery->where($filterOpen);
            $resQuery->where($filterOpen);
        } elseif ($this->statusFilter === 'won') {
            $filterWon = function ($q) use ($wonStage) {
                $q->where('closed_status', 'won');
                if ($wonStage) {
                    $q->orWhere('pipeline_stage_id', $wonStage->id);
                }
            };
            $rawQuery->where($filterWon);
            $resQuery->where($filterWon);
        } elseif ($this->statusFilter === 'lost') {
            $filterLost = function ($q) use ($lostStage) {
                $q->where('closed_status', 'lost');
                if ($lostStage) {
                    $q->orWhere('pipeline_stage_id', $lostStage->id);
                }
            };
            $rawQuery->where($filterLost);
            $resQuery->where($filterLost);
        }

        if ($this->ownerFilter) {
            $rawQuery->where('user_owner_id', $this->ownerFilter);
            $resQuery->where('user_owner_id', $this->ownerFilter);
        }

        $deals = $resQuery->orderByDesc('updated_at')->get();
        if ($deals->isEmpty()) {
            $deals = $rawQuery->orderByDesc('updated_at')->get();
        }

        $grouped = [];
        foreach ($deals as $deal) {
            $statusLower = strtolower((string) $deal->closed_status);
            $stageId = $deal->pipeline_stage_id;

            // Bi-directional status & stage alignment
            if ($statusLower === 'won' && $wonStage) {
                $stageId = $wonStage->id;
                if ($deal->pipeline_stage_id !== $wonStage->id) {
                    $deal->forceFill(['pipeline_stage_id' => $wonStage->id])->save();
                }
            } elseif ($statusLower === 'lost' && $lostStage) {
                $stageId = $lostStage->id;
                if ($deal->pipeline_stage_id !== $lostStage->id) {
                    $deal->forceFill(['pipeline_stage_id' => $lostStage->id])->save();
                }
            } elseif ($wonStage && $stageId == $wonStage->id && $statusLower !== 'won') {
                $deal->forceFill(['closed_status' => 'won', 'closed_at' => $deal->closed_at ?? now()])->save();
            } elseif ($lostStage && $stageId == $lostStage->id && $statusLower !== 'lost') {
                $deal->forceFill(['closed_status' => 'lost', 'closed_at' => $deal->closed_at ?? now()])->save();
            }

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

        $debugInfo = [
            'time' => date('Y-m-d H:i:s'),
            'statusFilter' => $this->statusFilter,
            'ownerFilter' => $this->ownerFilter,
            'stages' => $stages->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'pipeline_id' => $s->pipeline_id])->all(),
            'total_deals_unscoped' => Deal::withoutGlobalScopes()->count(),
            'total_deals_scoped' => Deal::count(),
            'query_deals_count' => $deals->count(),
            'deals_detail' => $deals->map(fn ($d) => [
                'id' => $d->id,
                'external_id' => $d->external_id,
                'title' => $d->title,
                'pipeline_id' => $d->pipeline_id,
                'pipeline_stage_id' => $d->pipeline_stage_id,
                'closed_status' => $d->closed_status,
                'closed_at' => $d->closed_at?->toIso8601String(),
                'team_id' => $d->team_id,
            ])->all(),
            'grouped_counts' => collect($grouped)->map(fn ($col) => count($col))->all(),
        ];

        file_put_contents(
            storage_path('logs/crm-debug.log'),
            json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n-------------------\n",
            FILE_APPEND
        );
        \Illuminate\Support\Facades\Log::info('[CRM-DEBUG] DealKanban::getDealsByStage', $debugInfo);

        return $grouped;
    }

    public function markWon(string $externalId): void
    {
        $deal = Deal::query()->where('external_id', $externalId)->first();
        if (! $deal) {
            return;
        }

        $wonStage = PipelineStage::query()
            ->where('pipeline_id', $deal->pipeline_id)
            ->where(fn ($q) => $q->where('name', 'like', '%won%')->orWhere('name', 'like', '%Won%'))
            ->first();

        $deal->forceFill([
            'closed_at' => now(),
            'closed_status' => 'won',
            'pipeline_stage_id' => $wonStage?->id ?? $deal->pipeline_stage_id,
        ])->save();

        file_put_contents(storage_path('logs/crm-debug.log'), "[MARK WON] Deal {$deal->id} status: won, stage: {$deal->pipeline_stage_id}\n", FILE_APPEND);
    }

    public function markLost(string $externalId): void
    {
        $deal = Deal::query()->where('external_id', $externalId)->first();
        if (! $deal) {
            return;
        }

        $lostStage = PipelineStage::query()
            ->where('pipeline_id', $deal->pipeline_id)
            ->where(fn ($q) => $q->where('name', 'like', '%lost%')->orWhere('name', 'like', '%Lost%'))
            ->first();

        $deal->forceFill([
            'closed_at' => now(),
            'closed_status' => 'lost',
            'pipeline_stage_id' => $lostStage?->id ?? $deal->pipeline_stage_id,
        ])->save();

        file_put_contents(storage_path('logs/crm-debug.log'), "[MARK LOST] Deal {$deal->id} status: lost, stage: {$deal->pipeline_stage_id}\n", FILE_APPEND);
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
        ])->save();

        file_put_contents(storage_path('logs/crm-debug.log'), "[REOPEN] Deal {$deal->id} reopened\n", FILE_APPEND);
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

            $stageNameLower = strtolower($stage?->name ?? '');
            if (str_contains($stageNameLower, 'won')) {
                $deal->closed_at = $deal->closed_at ?? now();
                $deal->closed_status = 'won';
            } elseif (str_contains($stageNameLower, 'lost')) {
                $deal->closed_at = $deal->closed_at ?? now();
                $deal->closed_status = 'lost';
            } else {
                $deal->closed_at = null;
                $deal->closed_status = null;
            }
        }

        $deal->save();

        file_put_contents(storage_path('logs/crm-debug.log'), "[MOVE DEAL] Deal {$deal->id} moved to stage {$stageId}, closed_status: {$deal->closed_status}, closed_at: {$deal->closed_at}\n", FILE_APPEND);
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
