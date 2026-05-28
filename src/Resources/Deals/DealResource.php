<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LeadDealContactSection;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LineItemsRepeater;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
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
    use UsesExternalIdRouting;

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

    public static function form(Schema $schema): Schema
    {
        $details = [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(5)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('amount')
                    ->label(__('laravel-crm-filament::labels.money.value'))
                    ->numeric()
                    ->prefix(fn ($get) => $get('currency') ?: config('laravel-crm.default_currency', 'USD')),

                Forms\Components\Select::make('currency')
                    ->label(__('laravel-crm-filament::labels.fields.currency'))
                    ->options(fn () => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies())
                    ->searchable()
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
            $details[] = $customFields;
        }

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        LeadDealContactSection::contactColumn(),
                        LeadDealContactSection::organizationColumn(),
                    ]),

                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make(__('laravel-crm-filament::labels.sections.details'))
                            ->schema($details),

                        Section::make(__('laravel-crm-filament::labels.sections.products'))
                            ->schema([
                                LineItemsRepeater::products('deal_product_id'),
                            ]),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deal_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->limit(50)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhereHas('person', function (Builder $q) use ($search): void {
                                $q->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('middle_name', 'like', "%{$search}%")
                                    ->orWhere('maiden_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('organization', function (Builder $q) use ($search): void {
                                $q->where('name', 'like', "%{$search}%");
                            });
                    }),

                Tables\Columns\TextColumn::make('labels.name')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->badge()
                    ->limitList(3),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('laravel-crm-filament::labels.money.value'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('person.name')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('laravel-crm-filament::labels.fields.organization'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expected_close')
                    ->label(__('laravel-crm-filament::labels.sales.expected_close'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pipelineStage.name')
                    ->label(__('laravel-crm-filament::labels.sales.stage'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->multiple()
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('labels')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->multiple()
                    ->relationship('labels', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('pipeline_stage_id')
                    ->label(__('laravel-crm-filament::labels.sales.stage'))
                    ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id')),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button(),
                Actions\EditAction::make()
                    ->button(),
                Actions\DeleteAction::make()
                    ->button()
                    ->requiresConfirmation(),
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

    /**
     * @return array<int, Actions\Action>
     */
    public static function listKanbanToggleActions(string $current): array
    {
        return [
            Actions\Action::make('view_list')
                ->label(__('laravel-crm-filament::labels.actions.list_view'))
                ->icon('heroicon-o-list-bullet')
                ->color($current === 'list' ? 'primary' : 'gray')
                ->url(static::getUrl('index')),

            Actions\Action::make('view_kanban')
                ->label(__('laravel-crm-filament::labels.actions.kanban_view'))
                ->icon('heroicon-o-view-columns')
                ->color($current === 'kanban' ? 'primary' : 'gray')
                ->url(static::getUrl('kanban')),
        ];
    }

    public static function markWonAction(): Actions\Action
    {
        return Actions\Action::make('markWon')
            ->label(__('laravel-crm-filament::labels.actions.mark_won'))
            ->icon('heroicon-o-trophy')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (?Deal $record): bool => $record !== null && $record->closed_at === null)
            ->action(function (Deal $record): void {
                $record->forceFill([
                    'closed_status' => 'won',
                    'closed_at' => now(),
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.mark_won'))
                    ->success()
                    ->send();
            });
    }

    public static function markLostAction(): Actions\Action
    {
        return Actions\Action::make('markLost')
            ->label(__('laravel-crm-filament::labels.actions.mark_lost'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (?Deal $record): bool => $record !== null && $record->closed_at === null)
            ->action(function (Deal $record): void {
                $record->forceFill([
                    'closed_status' => 'lost',
                    'closed_at' => now(),
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.mark_lost'))
                    ->success()
                    ->send();
            });
    }

    public static function reopenAction(): Actions\Action
    {
        return Actions\Action::make('reopen')
            ->label(__('laravel-crm-filament::labels.actions.reopen'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (?Deal $record): bool => $record !== null && $record->closed_at !== null)
            ->action(function (Deal $record): void {
                $record->forceFill([
                    'closed_status' => null,
                    'closed_at' => null,
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.reopen'))
                    ->success()
                    ->send();
            });
    }
}
