<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

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
            'all' => Tab::make(__('laravel-crm-filament::labels.misc.all') ?? 'All'),
            'open' => Tab::make(__('laravel-crm-filament::labels.sales.open') ?? 'Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('closed_at')),
            'won' => Tab::make(__('laravel-crm-filament::labels.actions.mark_won') ?? 'Won')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->where('closed_status', 'won')->orWhere('closed_status', 'Won')->orWhere('won', true))),
            'lost' => Tab::make(__('laravel-crm-filament::labels.actions.mark_lost') ?? 'Lost')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->where('closed_status', 'lost')->orWhere('closed_status', 'Lost')->orWhere('won', false))),
        ];
    }
}
