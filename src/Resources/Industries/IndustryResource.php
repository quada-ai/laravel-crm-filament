<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Industries;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Industry;
use VentureDrake\LaravelCrmFilament\Resources\Industries\Pages\CreateIndustry;
use VentureDrake\LaravelCrmFilament\Resources\Industries\Pages\EditIndustry;
use VentureDrake\LaravelCrmFilament\Resources\Industries\Pages\ListIndustries;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static ?string $slug = 'industries';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

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
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }
}
