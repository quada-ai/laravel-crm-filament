<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use BackedEnum;
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

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    public function getStages(): Collection
    {
        $pipelineIds = Pipeline::query()
            ->where('model', Quote::class)
            ->pluck('id');

        return PipelineStage::query()
            ->whereIn('pipeline_id', $pipelineIds)
            ->orderBy('order')
            ->get();
    }

    public function getQuotesByStage(): array
    {
        $quotes = Quote::query()
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->whereNotNull('pipeline_stage_id')
            ->orderByDesc('updated_at')
            ->get();

        return $quotes->groupBy('pipeline_stage_id')->all();
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
}
