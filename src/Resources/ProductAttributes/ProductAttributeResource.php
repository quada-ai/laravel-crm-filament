<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ProductAttributes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ProductAttribute;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages\CreateProductAttribute;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages\EditProductAttribute;
use VentureDrake\LaravelCrmFilament\Resources\ProductAttributes\Pages\ListProductAttributes;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static ?string $slug = 'product-attributes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

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
            ])
            ->defaultSort('name')
            ->recordActions([
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
            'index' => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'edit' => EditProductAttribute::route('/{record}/edit'),
        ];
    }
}
