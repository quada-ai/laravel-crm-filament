<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TeamMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
