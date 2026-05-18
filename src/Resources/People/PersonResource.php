<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\ContactFieldsSchema;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedSearch;
use VentureDrake\LaravelCrmFilament\RelationManagers\CallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\MeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\NotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\TasksRelationManager;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedGlobalSearch;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\CreatePerson;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\EditPerson;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ListPeople;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ViewPerson;

class PersonResource extends Resource
{
    use HasCrmCustomFields;
    use HasEncryptedGlobalSearch;

    protected static ?string $model = Person::class;

    protected static ?string $slug = 'people';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Contacts';
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('first_name')->maxLength(255),
                Forms\Components\TextInput::make('last_name')->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('middle_name')->maxLength(255),
                Forms\Components\TextInput::make('maiden_name')->maxLength(255),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('title')->maxLength(50),
                Forms\Components\Select::make('gender')->options([
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other',
                ]),
                Forms\Components\DatePicker::make('birthday'),
            ]),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label('Owner')
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            ContactFieldsSchema::phonesRepeater(),
            ContactFieldsSchema::emailsRepeater(),
            ContactFieldsSchema::addressesRepeater(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Person::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $encrypted = config('laravel-crm.encrypt_db_fields', false);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->sortable(!$encrypted)
                    ->searchable(!$encrypted)
                    ->limit(40),

                Tables\Columns\TextColumn::make('last_name')
                    ->sortable(!$encrypted)
                    ->searchable(!$encrypted)
                    ->limit(40),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label('Owner')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->when(
                $encrypted,
                fn (Table $t) => $t->modifyQueryUsing(
                    HasEncryptedSearch::modifyQuery(
                        fn ($r) => trim(($r->first_name ?? '').' '.($r->last_name ?? ''))
                    )
                )
            )
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
            NotesRelationManager::class,
            TasksRelationManager::class,
            CallsRelationManager::class,
            MeetingsRelationManager::class,
        ];
    }

public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'middle_name', 'maiden_name'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return trim((string) ($record->name ?? ($record->first_name.' '.$record->last_name)));
    }

    protected static function crmEncryptedSearchAccessor(): \Closure
    {
        return fn ($r) => trim((($r->first_name ?? '').' '.($r->middle_name ?? '').' '.($r->last_name ?? '').' '.($r->maiden_name ?? '')));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}

