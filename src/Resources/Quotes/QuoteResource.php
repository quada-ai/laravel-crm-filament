<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\LineItemsRepeater;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\MoneyTotalsRow;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\SalesDetailsSection;
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
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\CreateQuote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\EditQuote;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ListQuotes;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\QuoteKanban;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages\ViewQuote;

class QuoteResource extends Resource
{
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use UsesExternalIdRouting;

    protected static ?string $model = Quote::class;

    protected static ?string $slug = 'quotes';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Sales';
    }

    public static function form(Schema $schema): Schema
    {
        $details = SalesDetailsSection::make([
            'title' => true,
            'description' => true,
            'reference' => true,
            'currency' => true,
            'issueDateKey' => 'issue_at',
            'expiryDateKey' => 'expire_at',
            'terms' => true,
            'stage' => true,
            'owner' => true,
            'labels' => true,
            'labelsField' => fn () => static::labelsField(),
            'customFields' => static::crmCustomFieldsSection(Quote::class),
        ]);

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([$details]),

                Section::make(__('laravel-crm-filament::labels.sections.products'))
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        LineItemsRepeater::products('quote_product_id', 'unit_price')->defaultItems(1),
                        MoneyTotalsRow::make(),
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

                Tables\Columns\TextColumn::make('quote_id')
                    ->label(__('laravel-crm-filament::labels.fields.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('laravel-crm-filament::labels.fields.reference'))
                    ->toggleable(),

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

                Tables\Columns\TextColumn::make('person.name')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('laravel-crm-filament::labels.fields.organization'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('laravel-crm-filament::labels.money.total'))
                    ->money(fn ($record) => $record->currency ?: config('laravel-crm.default_currency', 'USD'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('issue_at')
                    ->label(__('laravel-crm-filament::labels.money.issue_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expire_at')
                    ->label(__('laravel-crm-filament::labels.money.expiry_date'))
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
                static::primaryBulkActionGroup(),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['quote_id', 'title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->title ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter(['ID' => $record->quote_id]);
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
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'kanban' => QuoteKanban::route('/kanban'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
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

    public static function acceptAction(): Action
    {
        return Action::make('accept')
            ->label(__('laravel-crm-filament::labels.actions.accept'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (?Quote $record): bool => $record !== null && $record->accepted_at === null)
            ->action(function (Quote $record): void {
                $record->forceFill([
                    'accepted_at' => now(),
                    'rejected_at' => null,
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.accept'))
                    ->success()
                    ->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('laravel-crm-filament::labels.actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (?Quote $record): bool => $record !== null && $record->rejected_at === null)
            ->action(function (Quote $record): void {
                $record->forceFill([
                    'rejected_at' => now(),
                    'accepted_at' => null,
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.reject'))
                    ->success()
                    ->send();
            });
    }

    public static function unacceptAction(): Action
    {
        return Action::make('unaccept')
            ->label(__('laravel-crm-filament::labels.actions.unaccept'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (?Quote $record): bool => $record !== null && $record->accepted_at !== null)
            ->action(function (Quote $record): void {
                $record->forceFill([
                    'accepted_at' => null,
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.unaccept'))
                    ->success()
                    ->send();
            });
    }

    public static function unrejectAction(): Action
    {
        return Action::make('unreject')
            ->label(__('laravel-crm-filament::labels.actions.unreject'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (?Quote $record): bool => $record !== null && $record->rejected_at !== null)
            ->action(function (Quote $record): void {
                $record->forceFill([
                    'rejected_at' => null,
                ])->save();

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.unreject'))
                    ->success()
                    ->send();
            });
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_quotes'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }
}
