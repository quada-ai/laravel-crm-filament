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
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\TaxRate;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\CreateProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\EditProduct;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ListProducts;
use VentureDrake\LaravelCrmFilament\Resources\Products\Pages\ViewProduct;

class ProductResource extends Resource
{
    use HasCrmCustomFields;

    protected static ?string $model = Product::class;

    protected static ?string $slug = 'products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 55;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Catalog';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('SKU')
                    ->maxLength(100),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('barcode')
                    ->label('Barcode (ISBN/UPC/GTIN)')
                    ->maxLength(100),
                Forms\Components\TextInput::make('product_category')
                    ->maxLength(255),
                Forms\Components\TextInput::make('unit')
                    ->maxLength(50),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit price')
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default(config('laravel-crm.default_currency', 'USD')),
                Forms\Components\Select::make('tax_rate_id')
                    ->label('Tax rate')
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
                ->label('Owner')
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Product::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('SKU')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product_category')
                    ->label('Category')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label('Owner')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\ViewAction::make(),
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
