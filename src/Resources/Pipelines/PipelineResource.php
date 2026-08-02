<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Pipelines;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\RelationManagers\PipelineStagesRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\CreatePipeline;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\EditPipeline;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\ListPipelines;
use VentureDrake\LaravelCrmFilament\Resources\Pipelines\Pages\ViewPipeline;

class PipelineResource extends Resource
{
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'pipeline';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = Pipeline::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $slug = 'pipelines';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-funnel';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('model')
                ->label(__('laravel-crm-filament::labels.fields.applies_to'))
                ->options([
                    Lead::class => 'Leads',
                    Deal::class => 'Deals',
                    Quote::class => 'Quotes',
                    Order::class => 'Orders',
                    Invoice::class => 'Invoices',
                    Delivery::class => 'Deliveries',
                    PurchaseOrder::class => 'Purchase Orders',
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
                    ->label(__('laravel-crm-filament::labels.fields.applies_to'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                Tables\Columns\TextColumn::make('pipelineStages_count')
                    ->label(__('laravel-crm-filament::labels.sales.stages'))
                    ->counts('pipelineStages')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_pipelines'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPipelines::route('/'),
            'create' => CreatePipeline::route('/create'),
            'view' => ViewPipeline::route('/{record}'),
            'edit' => EditPipeline::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PipelineStagesRelationManager::class,
        ];
    }
}
