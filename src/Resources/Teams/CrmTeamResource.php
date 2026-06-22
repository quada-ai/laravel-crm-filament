<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Teams;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrmFilament\Resources\Teams\Pages\CreateCrmTeam;
use VentureDrake\LaravelCrmFilament\Resources\Teams\Pages\EditCrmTeam;
use VentureDrake\LaravelCrmFilament\Resources\Teams\Pages\ListCrmTeams;
use VentureDrake\LaravelCrmFilament\Resources\Teams\Pages\ViewCrmTeam;
use VentureDrake\LaravelCrmFilament\Resources\Teams\RelationManagers\TeamMembersRelationManager;

class CrmTeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $slug = 'crm-teams';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Contacts';

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return 'CRM Teams';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('users');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('laravel-crm-filament::labels.fields.users'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('userCreated.name')
                    ->label(__('laravel-crm-filament::labels.fields.created_by'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TeamMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmTeams::route('/'),
            'create' => CreateCrmTeam::route('/create'),
            'view' => ViewCrmTeam::route('/{record}'),
            'edit' => EditCrmTeam::route('/{record}/edit'),
        ];
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_teams'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }
}
