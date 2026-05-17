<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrmFilament\Concerns\LogsCrmActivity;

class NotesRelationManager extends RelationManager
{
    use LogsCrmActivity;

    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('content')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('noted_at'),
            Forms\Components\Toggle::make('pinned'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\IconColumn::make('pinned')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('By')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Actions\CreateAction::make()
                    ->after(fn (\Illuminate\Database\Eloquent\Model $record, \Filament\Resources\RelationManagers\RelationManager $livewire) => static::logCrmActivity($livewire->getOwnerRecord(), $record)),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
