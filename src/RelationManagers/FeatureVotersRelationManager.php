<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FeatureVotersRelationManager extends RelationManager
{
    protected static string $relationship = 'voters';

    protected static ?string $title = 'Voters';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('voted_at')
                    ->label(__('laravel-crm-filament::labels.fields.voted_at'))
                    ->state(fn (Model $record) => $record->pivot?->created_at)
                    ->dateTime()
                    ->since()
                    ->tooltip(fn (Model $record): ?string => optional($record->pivot?->created_at)->toDateTimeString())
                    ->sortable(),
            ])
            ->defaultSort('pivot_created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
