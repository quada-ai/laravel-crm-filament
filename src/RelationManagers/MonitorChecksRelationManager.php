<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/**
 * Stub MonitorChecks relation manager. US-007 will flesh out the table
 * columns + filters + actions for surfacing the per-check history on the
 * Monitor show page. For now this provides a registrable class so
 * MonitorResource::getRelations() can reference it.
 */
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
            ->columns([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
