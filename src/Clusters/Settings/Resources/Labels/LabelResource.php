<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrmFilament\Clusters\Settings;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\Pages\CreateLabel;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\Pages\EditLabel;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\Pages\ListLabels;

class LabelResource extends Resource
{
    protected static ?string $model = Label::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'labels';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

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
                Forms\Components\ColorPicker::make('hex')->label('Color'),
            ]),
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
                Tables\Columns\ColorColumn::make('hex')->label('Color'),
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
            'index' => ListLabels::route('/'),
            'create' => CreateLabel::route('/create'),
            'edit' => EditLabel::route('/{record}/edit'),
        ];
    }
}
