<?php

namespace VentureDrake\LaravelCrmFilament\Resources\AddressTypes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\AddressType;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages\CreateAddressType;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages\EditAddressType;
use VentureDrake\LaravelCrmFilament\Resources\AddressTypes\Pages\ListAddressTypes;

class AddressTypeResource extends Resource
{
    use TranslatableResource;

    protected static string $resourceTranslationKey = 'address_type';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = AddressType::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $slug = 'address-types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 135;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
