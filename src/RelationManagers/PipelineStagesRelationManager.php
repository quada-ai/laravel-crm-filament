<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PipelineStagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pipelineStages';

    protected static ?string $title = 'Pipeline stages';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('laravel-crm-filament::labels.sections.pipeline_stages');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('order')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->hidden(),

            Forms\Components\ColorPicker::make('color')
                ->hidden(),

            Forms\Components\Select::make('pipeline_stage_probability_id')
                ->label(__('laravel-crm-filament::labels.sales.probability'))
                ->relationship('pipelineStageProbability', 'name')
                ->searchable()
                ->preload()
                ->hidden(),

            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\ColorColumn::make('color')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pipelineStageProbability.percent')
                    ->label(__('laravel-crm-filament::labels.sales.probability'))
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
