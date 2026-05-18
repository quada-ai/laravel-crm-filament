<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('accepted_at')->whereNull('rejected_at'))
                ->badge(fn () => Quote::query()->whereNull('accepted_at')->whereNull('rejected_at')->count() ?: null)
                ->badgeColor('primary'),
            'accepted' => Tab::make('Accepted')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('accepted_at'))
                ->badgeColor('success'),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('rejected_at'))
                ->badgeColor('danger'),
        ];
    }
}
