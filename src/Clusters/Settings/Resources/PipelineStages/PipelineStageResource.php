<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages\CreatePipelineStage;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages\EditPipelineStage;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\Pages\ListPipelineStages;

class PipelineStageResource extends Resource
{
    protected static ?string $model = PipelineStage::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'pipeline-stages';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\Select::make('pipeline_id')
                    ->label('Pipeline')
                    ->options(fn () => Pipeline::query()->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\ColorPicker::make('color'),
            ]),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pipeline.name')
                    ->label('Pipeline')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order')->sortable(),
                Tables\Columns\ColorColumn::make('color')->toggleable(),
            ])
            ->defaultSort('order')
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPipelineStages::route('/'),
            'create' => CreatePipelineStage::route('/create'),
            'edit' => EditPipelineStage::route('/{record}/edit'),
        ];
    }
}
