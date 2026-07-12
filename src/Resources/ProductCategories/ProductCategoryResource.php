<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductCategories;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ProductCategory;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\CreateProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\EditProductCategory;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ListProductCategories;
use VentureDrake\LaravelCrmFilament\Resources\ProductCategories\Pages\ViewProductCategory;

class ProductCategoryResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = ProductCategory::class;

    protected static ?string $slug = 'product-categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('laravel-crm-filament::labels.money.products'))
                    ->counts('products')
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
            ->label(__('laravel-crm-filament::labels.actions.back_to_product_categories'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'view' => ViewProductCategory::route('/{record}'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
