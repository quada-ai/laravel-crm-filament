<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(DealResource::getUrl('kanban')),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('closed_at'))
                ->badge(fn () => Deal::query()->whereNull('closed_at')->count() ?: null)
                ->badgeColor('success'),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('closed_at'))
                ->badgeColor('gray'),
        ];
    }
}
