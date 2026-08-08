<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use BackedEnum;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

class QuoteKanban extends Page
{
    protected static string $resource = QuoteResource::class;

    protected string $view = 'laravel-crm-filament::quotes.kanban';

    protected static ?string $title = 'Quote pipeline';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-view-columns';

    public ?int $ownerFilter = null;

    public ?string $statusFilter = 'all';

    protected function getHeaderActions(): array
    {
        return [
            ...QuoteResource::listKanbanToggleActions('kanban'),
            Actions\CreateAction::make()
                ->url(QuoteResource::getUrl('create')),
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
            ->whereIn('model', [Quote::class, 'Quote', 'quote'])
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

    public function getQuotesByStage(): array
    {
        $stages = $this->getStages();
        $stageIds = $stages->pluck('id')->all();
        $defaultStageId = $stages->first()?->id;

        $quotes = Quote::query()
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereNull('accepted_at')->whereNull('rejected_at'))
            ->when($this->statusFilter === 'accepted', fn ($q) => $q->whereNotNull('accepted_at'))
            ->when($this->statusFilter === 'rejected', fn ($q) => $q->whereNotNull('rejected_at'))
            ->when($this->ownerFilter, fn ($q) => $q->where('user_owner_id', $this->ownerFilter))
            ->orderByDesc('updated_at')
            ->get();

        $grouped = [];
        foreach ($quotes as $quote) {
            $stageId = $quote->pipeline_stage_id;
            if (! $stageId || ! in_array($stageId, $stageIds)) {
                $stageId = $defaultStageId;
            }

            if ($stageId) {
                if (! isset($grouped[$stageId])) {
                    $grouped[$stageId] = collect();
                }
                $grouped[$stageId]->push($quote);
            }
        }

        return $grouped;
    }

    public function markAccepted(string $externalId): void
    {
        $this->closeQuote($externalId, true);
    }

    public function markRejected(string $externalId): void
    {
        $this->closeQuote($externalId, false);
    }

    public function reopen(string $externalId): void
    {
        $quote = Quote::query()->where('external_id', $externalId)->first();
        if (! $quote) {
            return;
        }
        $quote->forceFill([
            'accepted_at' => null,
            'rejected_at' => null,
        ])->save();
    }

    protected function closeQuote(string $externalId, bool $accepted): void
    {
        $quote = Quote::query()->where('external_id', $externalId)->first();
        if (! $quote) {
            return;
        }
        if ($accepted) {
            $quote->accepted_at = now();
        } else {
            $quote->rejected_at = now();
        }
        $quote->save();
    }

    public function moveQuote(string $externalId, ?int $stageId): void
    {
        $quote = Quote::query()->where('external_id', $externalId)->first();
        if (! $quote) {
            return;
        }
        $quote->pipeline_stage_id = $stageId;
        if ($stageId) {
            $stage = PipelineStage::find($stageId);
            $quote->pipeline_id = $stage?->pipeline_id;
        }
        $quote->save();
    }

    public function getStageTotal(int $stageId, array $byStage): float
    {
        $rows = $byStage[$stageId] ?? collect();
        $sum = 0;
        foreach ($rows as $row) {
            $sum += (int) ($row->total ?? 0);
        }

        return $sum / 100;
    }
}
