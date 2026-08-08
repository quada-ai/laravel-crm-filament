<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Support\CrmTab;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...DealResource::listKanbanToggleActions('list'),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => CrmTab::make(__('laravel-crm-filament::labels.misc.all'), $this),
            'open' => CrmTab::make(__('laravel-crm-filament::labels.sales.open'), $this)
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereNull('closed_status')
                        ->whereNull('closed_at')
                        ->where(fn ($q) => 
                            $q->whereDoesntHave('pipelineStage')
                                ->orWhereHas('pipelineStage', fn ($s) => 
                                    $s->where('name', 'not like', '%won%')->where('name', 'not like', '%lost%')
                                )
                        )
                ),
            'won' => CrmTab::make(__('laravel-crm-filament::labels.actions.won'), $this)
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('closed_status', 'won')
                        ->orWhereHas('pipelineStage', fn ($s) => $s->where('name', 'like', '%won%'))
                ),
            'lost' => CrmTab::make(__('laravel-crm-filament::labels.actions.lost'), $this)
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('closed_status', 'lost')
                        ->orWhereHas('pipelineStage', fn ($s) => $s->where('name', 'like', '%lost%'))
                ),
        ];
    }
}
