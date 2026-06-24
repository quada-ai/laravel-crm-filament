<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Products;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\HasPrimaryBulkActions;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\ProductVariationsRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\CreateProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\EditProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ViewProduct;

class ProductResource extends Resource
{
    use HasCrmCustomFields;
    use HasLabels;
    use HasPrimaryBulkActions;
    use UsesExternalIdRouting;

    protected static ?string $model = Product::class;

    protected static ?string $slug = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 55;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Catalog';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
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
                Forms\Components\TextInput::make('product_category')
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->maxLength(50),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('unit_price')
                    ->label(__('laravel-crm-filament::labels.money.unit_price'))
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
                Forms\Components\Select::make('tax_rate_id')
                    ->label(__('laravel-crm-filament::labels.money.tax_rate'))
                    ->options(fn () => TaxRate::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('purchase_account')
                    ->maxLength(50),
                Forms\Components\TextInput::make('sales_account')
                    ->maxLength(50),
            ]),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
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
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('xero_contact_indicator')
                    ->label('')
                    ->state(fn ($record) => $record?->xeroItem !== null)
                    ->boolean(),

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
                    ->state(fn ($record) => optional($record->getDefaultPrice())->unit_price
                        ? optional($record->getDefaultPrice())->unit_price / 100
                        : null)
                    ->money(fn ($record) => $record?->getDefaultPrice()?->currency ?: config('laravel-crm.default_currency', 'USD')),

                Tables\Columns\TextColumn::make('taxRate.name')
                    ->label(__('laravel-crm-filament::labels.fields.tax'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('taxRate.rate')
                    ->label(__('laravel-crm-filament::labels.money.tax_rate'))
                    ->numeric()
                    ->suffix('%')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('active')
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
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('labels')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->multiple()
                    ->relationship('labels', 'name')
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

    public static function getRelations(): array
    {
        return [
            ProductVariationsRelationManager::class,
        ];
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
}
