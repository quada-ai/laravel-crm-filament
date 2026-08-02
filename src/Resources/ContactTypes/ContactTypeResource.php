<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ContactTypes;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\ContactType;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages\CreateContactType;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages\EditContactType;
use VentureDrake\LaravelCrmFilament\Resources\ContactTypes\Pages\ListContactTypes;

class ContactTypeResource extends Resource
{
    use TranslatableResource;

    protected static string $resourceTranslationKey = 'contact_type';
    protected static string $navigationGroupKey = 'settings';

    protected static ?string $model = ContactType::class;

    protected static ?string $slug = 'contact-types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 130;

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
            'index' => ListContactTypes::route('/'),
            'create' => CreateContactType::route('/create'),
            'edit' => EditContactType::route('/{record}/edit'),
        ];
    }
}
