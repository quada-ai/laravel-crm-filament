<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'timelineActivities';

    protected static ?string $title = 'Activity';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('laravel-crm-filament::labels.audit.activity');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label(__('laravel-crm-filament::labels.audit.event'))
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causeable_id')
                    ->label(__('laravel-crm-filament::labels.fields.by_user'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.timestamp'))
                    ->dateTime()
                    ->since()
                    ->tooltip(fn (Model $record): ?string => optional($record->created_at)->toDateTimeString())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
