<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\MeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\NotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\TasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\CreateDeal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\DealKanban;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\EditDeal;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ListDeals;
use VentureDrake\LaravelCrmFilament\Resources\Deals\Pages\ViewDeal;

class DealResource extends Resource
{
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;

    protected static ?string $model = Deal::class;

    protected static ?string $slug = 'deals';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Deal::query()->whereNull('closed_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix(fn ($get) => $get('currency') ?: config('laravel-crm.default_currency', 'USD')),

                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
            ]),

            Forms\Components\DatePicker::make('expected_close')
                ->label(__('laravel-crm-filament::labels.sales.expected_close')),

            Forms\Components\Select::make('pipeline_stage_id')
                ->label(__('laravel-crm-filament::labels.sales.pipeline_stage'))
                ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            static::labelsField(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Deal::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deal_id')
                    ->label(__('laravel-crm-filament::labels.fields.id'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('pipelineStage.name')
                    ->label(__('laravel-crm-filament::labels.sales.stage'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_close')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('pipeline_stage_id')
                    ->label(__('laravel-crm-filament::labels.sales.stage'))
                    ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id')),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                static::primaryBulkActionGroup(withPipelineStage: true),
                Actions\BulkActionGroup::make([
                    ExportsCsv::action(
                        columns: [
                            'ID' => fn ($r) => $r->deal_id,
                            'Title' => fn ($r) => $r->title,
                            'Amount' => fn ($r) => ($r->amount ?? 0) / 100,
                            'Currency' => fn ($r) => $r->currency,
                            'Expected close' => fn ($r) => $r->expected_close,
                            'Owner' => fn ($r) => optional($r->ownerUser)->name,
                            'Created' => fn ($r) => $r->created_at,
                        ],
                        filename: 'deals',
                    ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            TasksRelationManager::class,
            CallsRelationManager::class,
            MeetingsRelationManager::class,
            AuditsRelationManager::class,
            FilesRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['deal_id', 'title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->title ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter(['ID' => $record->deal_id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
            'kanban' => DealKanban::route('/kanban'),
            'create' => CreateDeal::route('/create'),
            'view' => ViewDeal::route('/{record}'),
            'edit' => EditDeal::route('/{record}/edit'),
        ];
    }
}
