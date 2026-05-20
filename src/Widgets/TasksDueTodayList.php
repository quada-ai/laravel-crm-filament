<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Task;

class TasksDueTodayList extends TableWidget
{
    protected static ?string $heading = 'Tasks due today';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Task::query()
                ->whereNull('completed_at')
                ->whereDate('due_at', today())
                ->orderBy('due_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('due_at')->time()->label(__('laravel-crm-filament::labels.money.due')),
                Tables\Columns\TextColumn::make('assignedToUser.name')->label(__('laravel-crm-filament::labels.fields.assignee'))->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('taskable_type')
                    ->label(__('laravel-crm-filament::labels.fields.linked_to'))
                    ->formatStateUsing(fn ($state) => class_basename($state ?? '')),
            ])
            ->paginated([5, 10])
            ->emptyStateHeading('No tasks due today');
    }
}
