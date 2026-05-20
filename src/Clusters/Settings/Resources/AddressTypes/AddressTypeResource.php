<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\Pages\CreateAddressType;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\Pages\EditAddressType;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\AddressTypes\Pages\ListAddressTypes;

class AddressTypeResource extends Resource
{
    protected static ?string $model = AddressType::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'address-types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

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
            'index' => ListAddressTypes::route('/'),
            'create' => CreateAddressType::route('/create'),
            'edit' => EditAddressType::route('/{record}/edit'),
        ];
    }
}
