<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MonitorChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'checks';

    protected static ?string $title = 'Checks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('laravel-crm-filament::labels.fields.type'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'uptime' => 'info',
                        'ssl' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('laravel-crm-filament::labels.fields.status'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'up', 'valid' => 'success',
                        'down', 'invalid', 'error' => 'danger',
                        'slow' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->label(__('laravel-crm-filament::labels.fields.status_code'))
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('response_time')
                    ->label(__('laravel-crm-filament::labels.fields.response_time'))
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('error_message')
                    ->label(__('laravel-crm-filament::labels.fields.error_message'))
                    ->limit(60)
                    ->tooltip(fn (Model $record): ?string => $record->error_message)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('checked_at')
                    ->label(__('laravel-crm-filament::labels.fields.checked_at'))
                    ->dateTime()
                    ->since()
                    ->tooltip(fn (Model $record): ?string => optional($record->checked_at)->toDateTimeString())
                    ->sortable(),
            ])
            ->defaultSort('checked_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
