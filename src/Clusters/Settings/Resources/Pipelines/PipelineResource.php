<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\Pages\CreatePipeline;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\Pages\EditPipeline;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\Pages\ListPipelines;

class PipelineResource extends Resource
{
    protected static ?string $model = Pipeline::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'pipelines';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('model')
                ->label('Applies to')
                ->options([
                    \VentureDrake\LaravelCrm\Models\Lead::class => 'Leads',
                    \VentureDrake\LaravelCrm\Models\Deal::class => 'Deals',
                    \VentureDrake\LaravelCrm\Models\Quote::class => 'Quotes',
                    \VentureDrake\LaravelCrm\Models\Order::class => 'Orders',
                    \VentureDrake\LaravelCrm\Models\Invoice::class => 'Invoices',
                    \VentureDrake\LaravelCrm\Models\Delivery::class => 'Deliveries',
                    \VentureDrake\LaravelCrm\Models\PurchaseOrder::class => 'Purchase Orders',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Applies to')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                Tables\Columns\TextColumn::make('pipelineStages_count')
                    ->label('Stages')
                    ->counts('pipelineStages')
                    ->toggleable(),
            ])
            ->defaultSort('name')
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
            'index' => ListPipelines::route('/'),
            'create' => CreatePipeline::route('/create'),
            'edit' => EditPipeline::route('/{record}/edit'),
        ];
    }
}
