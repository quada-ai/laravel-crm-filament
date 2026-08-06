<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Support\CrmTab;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => CrmTab::make('All'),
            'open' => CrmTab::make('Open')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('completed_at')),
            'today' => CrmTab::make('Today')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('completed_at')->whereDate('due_at', today()))
                ->badge(fn () => Task::query()->whereNull('completed_at')->whereDate('due_at', today())->count() ?: null)
                ->badgeColor('warning'),
            'overdue' => CrmTab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('completed_at')->whereDate('due_at', '<', today()))
                ->badge(fn () => Task::query()->whereNull('completed_at')->whereDate('due_at', '<', today())->count() ?: null)
                ->badgeColor('danger'),
            'completed' => CrmTab::make('Completed')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('completed_at')),
        ];
    }
}
