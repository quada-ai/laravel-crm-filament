<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FieldGroupFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Fields';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('laravel-crm-filament::labels.sales.fields');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('laravel-crm-filament::labels.fields.type')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('required')
                    ->label(__('laravel-crm-filament::labels.fields.required'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('default')
                    ->label(__('laravel-crm-filament::labels.fields.default')),
                Tables\Columns\IconColumn::make('system')
                    ->label(__('laravel-crm-filament::labels.fields.system'))
                    ->boolean(),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
