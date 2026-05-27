<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FilesRelationManager;
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
        $components = [
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('reference')->maxLength(100),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
                Forms\Components\Select::make('pipeline_stage_id')
                    ->label(__('laravel-crm-filament::labels.sales.stage'))
                    ->options(fn () => PipelineStage::query()->orderBy('order')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\DatePicker::make('issue_at')->label(__('laravel-crm-filament::labels.money.issue_date')),
                Forms\Components\DatePicker::make('expire_at')->label(__('laravel-crm-filament::labels.money.expiry_date')),
            ]),

            // Line items
            Forms\Components\Repeater::make('products')
                ->label(__('laravel-crm-filament::labels.money.line_items'))
                ->schema([
                    Forms\Components\Hidden::make('quote_product_id'),
                    Forms\Components\Select::make('id')
                        ->label(__('laravel-crm-filament::labels.money.product'))
                        ->options(fn () => Product::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $product = Product::find($state);
                            if ($product) {
                                $price = $product->getDefaultPrice();
                                $set('unit_price', $price ? $price->price / 100 : 0);
                            }
                        }),
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            $set('amount', (float) $state * (float) $get('unit_price'));
                        }),
                    Forms\Components\TextInput::make('unit_price')
                        ->label(__('laravel-crm-filament::labels.money.unit_price'))
                        ->numeric()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                            $set('amount', (float) $state * (float) $get('quantity'));
                        }),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->readOnly(),
                    Forms\Components\TextInput::make('comments')
                        ->maxLength(255),
                ])
                ->columns(5)
                ->addActionLabel('Add line item')
                ->defaultItems(0)
                ->reorderable()
                ->columnSpanFull(),

            // Totals
            Grid::make(4)->schema([
                Forms\Components\TextInput::make('sub_total')
                    ->label(__('laravel-crm-filament::labels.money.subtotal'))
                    ->numeric(),
                Forms\Components\TextInput::make('discount')
                    ->numeric(),
                Forms\Components\TextInput::make('tax')
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->numeric(),
            ]),

            Forms\Components\Textarea::make('terms')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            static::labelsField(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Quote::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
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
            AuditsRelationManager::class,
            FilesRelationManager::class,
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

    public static function acceptAction(): Actions\Action
    {
        return Actions\Action::make('accept')
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

    public static function rejectAction(): Actions\Action
    {
        return Actions\Action::make('reject')
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

    public static function unacceptAction(): Actions\Action
    {
        return Actions\Action::make('unaccept')
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

    public static function unrejectAction(): Actions\Action
    {
        return Actions\Action::make('unreject')
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
}
