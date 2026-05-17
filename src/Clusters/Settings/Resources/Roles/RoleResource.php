<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles\Pages\CreateRole;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles\Pages\EditRole;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles\Pages\ListRoles;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guard_name')
                    ->default('web')
                    ->required()
                    ->maxLength(255),
            ]),
            Forms\Components\CheckboxList::make('permissions')
                ->relationship('permissions', 'name')
                ->options(fn () => Permission::query()->orderBy('name')->pluck('name', 'id'))
                ->columns(3)
                ->searchable()
                ->bulkToggleable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('guard_name')->toggleable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\EditAction::make()
                    ->visible(fn ($record) => ! in_array($record->name, ['Owner', 'Admin'], true)),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! in_array($record->name, ['Owner', 'Admin'], true);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! in_array($record->name, ['Owner', 'Admin'], true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
