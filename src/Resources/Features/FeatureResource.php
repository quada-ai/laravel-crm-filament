<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FeatureCommentsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FeatureVotersRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\CreateFeature;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\EditFeature;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\FeatureKanban;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\ListFeatures;
use VentureDrake\LaravelCrmFilament\Resources\Features\Pages\ViewFeature;

class FeatureResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use UsesExternalIdRouting;

    protected static ?string $model = Feature::class;

    protected static ?string $slug = 'features';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Roadmap';
    }

    public static function isEnabled(): bool
    {
        return LaravelCrmPlugin::get()->isModuleEnabled('features');
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

            Forms\Components\Toggle::make('is_public')
                ->label(__('laravel-crm-filament::labels.fields.is_public'))
                ->default(false),

            Forms\Components\Select::make('feature_status_id')
                ->label(__('laravel-crm-filament::labels.fields.feature_status'))
                ->options(fn () => FeatureStatus::query()
                    ->orderBy('order')
                    ->get()
                    ->mapWithKeys(fn ($status) => [$status->id => $status->name])
                    ->all())
                ->searchable()
                ->preload(),

            Grid::make(2)->schema([
                Forms\Components\Select::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('user_assigned_id')
                    ->label(__('laravel-crm-filament::labels.fields.assigned_to'))
                    ->relationship('assignedToUser', 'name')
                    ->searchable()
                    ->preload(),
            ]),

            static::labelsField(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Feature::class)) {
            $details[] = $customFields;
        }

        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema($details),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mirrors core CRM's Livewire FeatureIndex::headers() column set:
                // created_at, feature_id, title, status.name (non-sortable badge),
                // votes_count, comments_count, is_public (Yes/No). The previously
                // surfaced labels/views_count/ownerUser columns are dropped to
                // stay column-for-column aligned with core.
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('feature_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('laravel-crm-filament::labels.fields.title'))
                    ->sortable()
                    ->limit(60)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('laravel-crm-filament::labels.fields.status'))
                    ->badge()
                    ->color(function ($state, $record) {
                        $hex = $record?->status?->color;

                        if (! $hex) {
                            return 'gray';
                        }

                        return '#' . ltrim($hex, '#');
                    }),

                Tables\Columns\TextColumn::make('votes_count')
                    ->label(__('laravel-crm-filament::labels.fields.votes'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label(__('laravel-crm-filament::labels.fields.comments'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_public')
                    ->label(__('laravel-crm-filament::labels.fields.public'))
                    ->formatStateUsing(fn (?bool $state): string => $state
                        ? __('laravel-crm::lang.yes')
                        : __('laravel-crm::lang.no'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Mirrors core's drawer: only Status (multi) and Visibility
                // (is_public ternary). Owner + labels filters are dropped.
                Tables\Filters\SelectFilter::make('feature_status_id')
                    ->label(__('laravel-crm-filament::labels.fields.status'))
                    ->multiple()
                    ->options(fn () => FeatureStatus::query()->orderBy('order')->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label(__('laravel-crm-filament::labels.fields.public')),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                static::primaryBulkActionGroup(),
                Actions\BulkActionGroup::make([
                    ExportsCsv::action(
                        columns: [
                            'ID' => fn ($r) => $r->feature_id,
                            'Title' => fn ($r) => $r->title,
                            'Status' => fn ($r) => optional($r->status)->name,
                            'Public' => fn ($r) => $r->is_public ? 'yes' : 'no',
                            'Votes' => fn ($r) => $r->votes_count,
                            'Comments' => fn ($r) => $r->comments_count,
                            'Views' => fn ($r) => $r->views_count,
                            'Owner' => fn ($r) => optional($r->ownerUser)->name,
                            'Created' => fn ($r) => $r->created_at,
                        ],
                        filename: 'features',
                    ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CrmActivitiesRelationManager::class,
            CrmNotesRelationManager::class,
            CrmTasksRelationManager::class,
            CrmCallsRelationManager::class,
            CrmMeetingsRelationManager::class,
            CrmLunchesRelationManager::class,
            CrmFilesRelationManager::class,
            FeatureVotersRelationManager::class,
            FeatureCommentsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['feature_id', 'title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->title ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter(['ID' => $record->feature_id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatures::route('/'),
            'kanban' => FeatureKanban::route('/kanban'),
            'create' => CreateFeature::route('/create'),
            'view' => ViewFeature::route('/{record}'),
            'edit' => EditFeature::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema(fn (?Feature $record) => array_merge([
                    TextEntry::make('created_at')
                        ->label(__('laravel-crm-filament::labels.fields.created'))
                        ->since(),

                    TextEntry::make('feature_id')
                        ->label(__('laravel-crm-filament::labels.fields.number')),

                    TextEntry::make('description')
                        ->label(__('laravel-crm-filament::labels.fields.description'))
                        ->columnSpanFull(),

                    TextEntry::make('status.name')
                        ->label(__('laravel-crm-filament::labels.fields.feature_status'))
                        ->badge()
                        ->color(function ($state, $record) {
                            $hex = $record?->status?->color;

                            if (! $hex) {
                                return 'gray';
                            }

                            return '#' . ltrim($hex, '#');
                        }),

                    TextEntry::make('is_public')
                        ->label(__('laravel-crm-filament::labels.fields.is_public'))
                        ->state(fn ($record) => $record?->is_public
                            ? __('laravel-crm::lang.yes')
                            : __('laravel-crm::lang.no')),

                    TextEntry::make('votes_count')
                        ->label(__('laravel-crm-filament::labels.fields.votes')),

                    TextEntry::make('comments_count')
                        ->label(__('laravel-crm-filament::labels.fields.comments')),

                    TextEntry::make('views_count')
                        ->label(__('laravel-crm-filament::labels.fields.views')),

                    TextEntry::make('labels.name')
                        ->label(__('laravel-crm-filament::labels.fields.labels'))
                        ->badge()
                        ->color(function ($state, $record) {
                            $label = $record?->labels?->firstWhere('name', $state);
                            $hex = $label?->hex;

                            if (! $hex) {
                                return 'gray';
                            }

                            return '#' . ltrim($hex, '#');
                        }),

                    TextEntry::make('submittedBy.name')
                        ->label(__('laravel-crm-filament::labels.fields.submitted_by'))
                        ->placeholder('—'),

                    TextEntry::make('ownerUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.owner'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),

                    TextEntry::make('assignedToUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.assigned_to'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),
                ], $record ? static::crmCustomFieldEntries($record, false) : [])),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Feature $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Feature) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),
        ])->columns(1);
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_features'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    /**
     * @return array<int, Action>
     */
    public static function listKanbanToggleActions(string $current): array
    {
        return [
            Action::make('viewToggle')
                ->label('')
                ->view('laravel-crm-filament::components.list-kanban-toggle', [
                    'current' => $current,
                    'listUrl' => static::getUrl('index'),
                    'kanbanUrl' => static::getUrl('kanban'),
                ]),
        ];
    }
}
