<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Labels;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\CreateLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\EditLabel;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ListLabels;
use VentureDrake\LaravelCrmFilament\Resources\Labels\Pages\ViewLabel;

class LabelResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = Label::class;

    protected static ?string $slug = 'labels';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\ColorPicker::make('hex')->label(__('laravel-crm-filament::labels.fields.color')),
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
                Tables\Columns\TextColumn::make('hex')
                    ->label(__('laravel-crm-filament::labels.fields.color'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? '#' . ltrim($state, '#') : null)
                    ->color(fn ($record): string => '#' . ltrim($record->hex ?? '6b7280', '#')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('laravel-crm-filament::labels.fields.updated'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_labels'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLabels::route('/'),
            'create' => CreateLabel::route('/create'),
            'view' => ViewLabel::route('/{record}'),
            'edit' => EditLabel::route('/{record}/edit'),
        ];
    }
}
