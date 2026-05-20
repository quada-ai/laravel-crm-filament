<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Team;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\CreateCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\EditCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\ListCrmTeams;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\Pages\ViewCrmTeam;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\CrmTeams\RelationManagers\TeamMembersRelationManager;

class CrmTeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'crm-teams';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users'),
                Tables\Columns\TextColumn::make('userCreated.name')
                    ->label('Created by')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
}
