<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Leads\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('converted_at'))
                ->badge(fn () => Lead::query()->whereNull('converted_at')->count() ?: null)
                ->badgeColor('primary'),
            'converted' => Tab::make('Converted')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('converted_at'))
                ->badgeColor('success'),
        ];
    }
}
