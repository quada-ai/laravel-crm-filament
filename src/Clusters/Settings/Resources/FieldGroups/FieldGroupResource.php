<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages\CreateFieldGroup;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages\EditFieldGroup;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\Pages\ListFieldGroups;

class FieldGroupResource extends Resource
{
    protected static ?string $model = FieldGroup::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'field-groups';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('handle')
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('handle')->toggleable(),
                Tables\Columns\TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label('Fields')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([Actions\EditAction::make()])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFieldGroups::route('/'),
            'create' => CreateFieldGroup::route('/create'),
            'edit' => EditFieldGroup::route('/{record}/edit'),
        ];
    }
}
