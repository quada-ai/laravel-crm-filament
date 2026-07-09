<?php

namespace VentureDrake\LaravelCrmFilament\Resources\PipelineStageProbabilities;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\PipelineStageProbability;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStageProbabilities\Pages\CreatePipelineStageProbability;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStageProbabilities\Pages\EditPipelineStageProbability;
use VentureDrake\LaravelCrmFilament\Resources\PipelineStageProbabilities\Pages\ListPipelineStageProbabilities;

class PipelineStageProbabilityResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = PipelineStageProbability::class;

    protected static ?string $slug = 'pipeline-stage-probabilities';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 45;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('percent')
                    ->label(__('laravel-crm-filament::labels.sales.probability_percent'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required()
                    ->suffix('%'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('percent')
                    ->label(__('laravel-crm-filament::labels.sales.probability'))
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),
            ])
            ->defaultSort('percent')
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
            'index' => ListPipelineStageProbabilities::route('/'),
            'create' => CreatePipelineStageProbability::route('/create'),
            'edit' => EditPipelineStageProbability::route('/{record}/edit'),
        ];
    }
}
