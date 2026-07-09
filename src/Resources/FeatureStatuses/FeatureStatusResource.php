<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\Pages\CreateFeatureStatus;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\Pages\EditFeatureStatus;
use VentureDrake\LaravelCrmFilament\Resources\FeatureStatuses\Pages\ListFeatureStatuses;

class FeatureStatusResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = FeatureStatus::class;

    protected static ?string $slug = 'feature-statuses';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-flag';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 88;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]),
            Forms\Components\ColorPicker::make('color'),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
            Grid::make(2)->schema([
                Forms\Components\Toggle::make('is_default')
                    ->label(__('laravel-crm-filament::labels.fields.is_default'))
                    ->default(false),
                Forms\Components\Toggle::make('is_closed')
                    ->label(__('laravel-crm-filament::labels.fields.is_closed'))
                    ->default(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('features'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\ColorColumn::make('color')->toggleable(),
                Tables\Columns\TextColumn::make('order')->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('laravel-crm-filament::labels.fields.is_default'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label(__('laravel-crm-filament::labels.fields.is_closed'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('features_count')
                    ->label(__('laravel-crm-filament::labels.sales.features'))
                    ->numeric()
                    ->toggleable(),
            ])
            ->defaultSort('order')
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
            'index' => ListFeatureStatuses::route('/'),
            'create' => CreateFeatureStatus::route('/create'),
            'edit' => EditFeatureStatus::route('/{record}/edit'),
        ];
    }
}
