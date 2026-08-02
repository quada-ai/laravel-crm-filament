<?php

namespace VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\OrganizationType;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\Pages\CreateOrganizationType;
use VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\Pages\EditOrganizationType;
use VentureDrake\LaravelCrmFilament\Resources\OrganizationTypes\Pages\ListOrganizationTypes;

class OrganizationTypeResource extends Resource
{
    use TranslatableResource;

    protected static string $resourceTranslationKey = 'organization_type';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = OrganizationType::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $slug = 'organization-types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 140;

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
            'index' => ListOrganizationTypes::route('/'),
            'create' => CreateOrganizationType::route('/create'),
            'edit' => EditOrganizationType::route('/{record}/edit'),
        ];
    }
}
