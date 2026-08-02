<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Roles;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Resources\Roles\Pages\CreateRole;
use VentureDrake\LaravelCrmFilament\Resources\Roles\Pages\EditRole;
use VentureDrake\LaravelCrmFilament\Resources\Roles\Pages\ListRoles;
use VentureDrake\LaravelCrmFilament\Resources\Roles\Pages\ViewRole;

class RoleResource extends Resource
{
    use TranslatableResource;

    protected static string $resourceTranslationKey = 'role';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('description')
                ->maxLength(255),
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
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('laravel-crm-filament::labels.fields.users'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label(__('laravel-crm-filament::labels.fields.permissions'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('crm_role')
                    ->label(__('laravel-crm-filament::labels.misc.crm')),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->visible(fn ($record) => ! in_array($record->name, ['Owner', 'Admin'], true)),
                Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! in_array($record->name, ['Owner', 'Admin'], true)),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function canEdit(Model $record): bool
    {
        return ! in_array($record->name, ['Owner', 'Admin'], true);
    }

    public static function canDelete(Model $record): bool
    {
        return ! in_array($record->name, ['Owner', 'Admin'], true);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_roles'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
