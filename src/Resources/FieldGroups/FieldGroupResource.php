<?php

namespace VentureDrake\LaravelCrmFilament\Resources\FieldGroups;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\CreateFieldGroup;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\EditFieldGroup;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\ListFieldGroups;
use VentureDrake\LaravelCrmFilament\Resources\FieldGroups\Pages\ViewFieldGroup;

class FieldGroupResource extends Resource
{
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'field_group';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = FieldGroup::class;

    protected static ?string $slug = 'field-groups';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 100;

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
                Tables\Columns\IconColumn::make('system')
                    ->label(__('laravel-crm-filament::labels.fields.system'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label(__('laravel-crm-filament::labels.fields.fields'))
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel()->requiresConfirmation(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function backToIndexAction(): Actions\Action
    {
        return Actions\Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_field_groups'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFieldGroups::route('/'),
            'create' => CreateFieldGroup::route('/create'),
            'view' => ViewFieldGroup::route('/{record}'),
            'edit' => EditFieldGroup::route('/{record}/edit'),
        ];
    }
}
