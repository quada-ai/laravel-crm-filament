<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ProductAttribute;

class ProductVariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'productVariations';

    protected static ?string $title = 'Variations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('product_attribute_id')
                ->label(__('laravel-crm-filament::labels.misc.attribute'))
                ->options(fn () => ProductAttribute::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(60)->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['external_id'] = (string) Uuid::uuid4();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
