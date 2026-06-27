<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Timezones;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Timezone;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages\CreateTimezone;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages\EditTimezone;
use VentureDrake\LaravelCrmFilament\Resources\Timezones\Pages\ListTimezones;

class TimezoneResource extends Resource
{
    protected static ?string $model = Timezone::class;

    protected static ?string $slug = 'timezones';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('offset')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('diff_from_gtm')
                    ->label(__('laravel-crm-filament::labels.misc.diff_from_gmt'))
                    ->required()
                    ->maxLength(50),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('offset')->toggleable(),
                Tables\Columns\TextColumn::make('diff_from_gtm')->label(__('laravel-crm-filament::labels.misc.diff_from_gmt'))->toggleable(),
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
            'index' => ListTimezones::route('/'),
            'create' => CreateTimezone::route('/create'),
            'edit' => EditTimezone::route('/{record}/edit'),
        ];
    }
}
