<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products;

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
use Illuminate\Database\Eloquent\Builder;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\CreateProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\EditProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ViewProduct;

class ProductResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'product';
    protected static string $navigationGroupKey = 'catalog';

    protected static ?string $model = Product::class;

    protected static ?string $slug = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 55;

    public static function getNavigationBadge(): ?string
    {
        $count = Product::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label(__('laravel-crm-filament::labels.money.sku'))
                    ->maxLength(100),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('barcode')
                    ->label(__('laravel-crm-filament::labels.money.barcode'))
                    ->maxLength(100),
                Forms\Components\Select::make('product_category')
                    ->label(__('laravel-crm-filament::labels.fields.category'))
                    ->options(fn () => ProductCategory::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label(__('laravel-crm-filament::labels.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('laravel-crm-filament::labels.fields.description'))
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return ProductCategory::create([
                            'external_id' => (string) Uuid::uuid4(),
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                        ])->id;
                    }),
                Forms\Components\TextInput::make('unit')
                    ->label(__('laravel-crm-filament::labels.fields.unit'))
                    ->maxLength(50),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('unit_price')
                    ->label(__('laravel-crm-filament::labels.money.unit_price'))
                    ->numeric(),
                Forms\Components\Select::make('currency')
                    ->label(__('laravel-crm-filament::labels.fields.currency'))
                    ->options(fn () => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies())
                    ->searchable()
                    ->required()
                    ->default(function () {
                        try {
                            return \VentureDrake\LaravelCrm\Models\Setting::currency()
                                ?? \VentureDrake\LaravelCrm\Models\Setting::where('name', 'currency')->first()?->value
                                ?? config('laravel-crm.default_currency', 'USD');
                        } catch (\Throwable $e) {
                            return config('laravel-crm.default_currency', 'USD');
                        }
                    }),
                Forms\Components\Select::make('tax_rate_id')
                    ->label(__('laravel-crm-filament::labels.money.tax_rate'))
                    ->options(fn () => TaxRate::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('purchase_account')
                    ->label(__('laravel-crm-filament::labels.fields.purchase_account'))
                    ->maxLength(50),
                Forms\Components\TextInput::make('sales_account')
                    ->label(__('laravel-crm-filament::labels.fields.sales_account'))
                    ->maxLength(50),
            ]),

            Forms\Components\Textarea::make('description')
                ->label(__('laravel-crm-filament::labels.fields.description'))
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->options(fn () => \VentureDrake\LaravelCrmFilament\Support\UserOptions::get())
                ->searchable()
                ->preload(),

            static::labelsField(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Product::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'productCategory',
                'taxRate',
                'ownerUser',
                'productPrices',
                'xeroItem',
                'labels',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('xero_contact_indicator')
                    ->label('')
                    ->state(fn ($record) => $record?->xeroItem !== null)
                    ->boolean()
                    ->visible(fn (): bool => LaravelCrmPlugin::get()->isModuleEnabled('xero')),

                Tables\Columns\TextColumn::make('code')
                    ->label(__('laravel-crm-filament::labels.money.sku'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label(__('laravel-crm-filament::labels.fields.category'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label(__('laravel-crm-filament::labels.fields.unit'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('laravel-crm-filament::labels.money.price'))
                    ->state(function ($record) {
                        $priceRecord = static::getDefaultPriceRecord($record);
                        return $priceRecord?->unit_price ? $priceRecord->unit_price / 100 : null;
                    })
                    ->money(function ($record) {
                        $priceRecord = static::getDefaultPriceRecord($record);
                        return $priceRecord?->currency ?: config('laravel-crm.default_currency', 'USD');
                    }),

                Tables\Columns\TextColumn::make('taxRate.name')
                    ->label(__('laravel-crm-filament::labels.money.tax'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('taxRate.rate')
                    ->label(__('laravel-crm-filament::labels.money.tax_rate'))
                    ->numeric()
                    ->suffix('%')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('laravel-crm-filament::labels.fields.active'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->multiple()
                    ->options(fn () => \VentureDrake\LaravelCrmFilament\Support\UserOptions::get())
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('labels')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->multiple()
                    ->options(fn () => \VentureDrake\LaravelCrm\Models\Label::pluck('name', 'id'))->query(function ($query, array $data) { if (empty($data['values'])) return $query; return $query->whereHas('labels', fn ($q) => $q->whereIn('crm_labels.id', $data['values'])); })
                    ->preload(),
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
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema(fn (?Product $record) => array_merge([
                    TextEntry::make('code')
                        ->label(__('laravel-crm-filament::labels.money.sku')),

                    TextEntry::make('barcode')
                        ->label(__('laravel-crm-filament::labels.money.barcode')),

                    TextEntry::make('purchase_account')
                        ->label(__('laravel-crm-filament::labels.fields.purchase_account')),

                    TextEntry::make('sales_account')
                        ->label(__('laravel-crm-filament::labels.fields.sales_account')),

                    TextEntry::make('unit')
                        ->label(__('laravel-crm-filament::labels.fields.unit')),

                    TextEntry::make('taxRate.name')
                        ->label(__('laravel-crm-filament::labels.money.tax')),

                    TextEntry::make('taxRate.rate')
                        ->label(__('laravel-crm-filament::labels.money.tax_rate'))
                        ->suffix('%'),

                    TextEntry::make('productCategory.name')
                        ->label(__('laravel-crm-filament::labels.fields.category')),

                    TextEntry::make('description')
                        ->label(__('laravel-crm-filament::labels.fields.description'))
                        ->columnSpanFull(),

                    TextEntry::make('ownerUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.owner'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),
                ], $record ? static::crmCustomFieldEntries($record, false) : [])),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Product $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Product) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),
        ])->columns(1);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_products'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getDefaultPriceRecord($record)
    {
        if (! $record) {
            return null;
        }

        $currency = config('laravel-crm.default_currency', 'USD');
        try {
            $settingModel = \VentureDrake\LaravelCrm\Models\Setting::query()
                ->where('name', 'currency')
                ->first();
            if ($settingModel && $settingModel->value) {
                $currency = $settingModel->value;
            }
        } catch (\Throwable $e) {
            // fall back to config default
        }

        return $record->productPrices()
            ->where('currency', $currency)
            ->first() ?? $record->productPrices()->first();
    }
}

