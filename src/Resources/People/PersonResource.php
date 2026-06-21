<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\ContactFieldsSchema;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedGlobalSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\CreatePerson;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\EditPerson;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ListPeople;
use VentureDrake\LaravelCrmFilament\Resources\People\Pages\ViewPerson;

class PersonResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasEncryptedGlobalSearch;
    use HasLabels;
    use UsesExternalIdRouting;

    protected static ?string $model = Person::class;

    protected static ?string $slug = 'people';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Contacts';
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
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload(),

            static::labelsField(),

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
                    ->sortable(! $encrypted)
                    ->searchable(! $encrypted)
                    ->limit(40),

                Tables\Columns\TextColumn::make('last_name')
                    ->sortable(! $encrypted)
                    ->searchable(! $encrypted)
                    ->limit(40),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
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
                        fn ($r) => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''))
                    )
                )
            )
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    ExportsCsv::action(
                        columns: [
                            'First name' => fn ($r) => $r->first_name,
                            'Last name' => fn ($r) => $r->last_name,
                            'Organization' => fn ($r) => optional($r->organization)->name,
                            'Owner' => fn ($r) => optional($r->ownerUser)->name,
                            'Created' => fn ($r) => $r->created_at,
                        ],
                        filename: 'people',
                    ),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.identity'))
                ->schema(fn (?Person $record) => array_merge([
                    TextEntry::make('first_name')
                        ->label(__('laravel-crm-filament::labels.fields.first_name')),

                    TextEntry::make('last_name')
                        ->label(__('laravel-crm-filament::labels.fields.last_name')),

                    TextEntry::make('middle_name')
                        ->label(__('laravel-crm-filament::labels.fields.middle_name')),

                    TextEntry::make('email')
                        ->label(__('laravel-crm-filament::labels.fields.email'))
                        ->state(function ($record) {
                            $email = $record?->emails()->first();

                            if (! $email) {
                                return null;
                            }

                            return trim(($email->address ?? '') . ($email->type ? ' (' . $email->type . ')' : ''));
                        }),

                    TextEntry::make('phone')
                        ->label(__('laravel-crm-filament::labels.fields.phone'))
                        ->state(function ($record) {
                            $phone = $record?->phones()->first();

                            if (! $phone) {
                                return null;
                            }

                            return trim(($phone->number ?? '') . ($phone->type ? ' (' . $phone->type . ')' : ''));
                        }),
                ], $record ? static::crmCustomFieldEntries($record, false) : [])),

            Section::make(__('laravel-crm-filament::labels.sections.contact'))
                ->schema([
                    TextEntry::make('organization.name')
                        ->label(__('laravel-crm-filament::labels.fields.organization'))
                        ->url(fn ($record) => $record?->organization
                            ? OrganizationResource::getUrl('view', ['record' => $record->organization])
                            : null),

                    TextEntry::make('addresses')
                        ->label(__('laravel-crm-filament::labels.fields.addresses'))
                        ->state(fn ($record) => $record instanceof Person ? static::formatAddresses($record) : null)
                        ->columnSpanFull(),
                ]),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Person $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Person) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),
        ])->columns(1);
    }

    protected static function formatAddresses(Person $record): ?string
    {
        $addresses = $record->addresses()->get();

        if ($addresses->isEmpty()) {
            return null;
        }

        $lines = $addresses->map(function ($address) {
            $parts = array_filter([
                $address->line1 ?? null,
                $address->city ?? null,
                $address->state ?? null,
                $address->code ?? null,
                $address->country ?? null,
            ]);

            return $parts === [] ? null : implode(', ', $parts);
        })->filter()->values();

        return $lines->isEmpty() ? null : $lines->implode("\n");
    }

    public static function getRelations(): array
    {
        return [
            CrmActivitiesRelationManager::class,
            CrmNotesRelationManager::class,
            CrmTasksRelationManager::class,
            CrmCallsRelationManager::class,
            CrmMeetingsRelationManager::class,
            CrmLunchesRelationManager::class,
            CrmFilesRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'middle_name', 'maiden_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return trim((string) ($record->name ?? ($record->first_name . ' ' . $record->last_name)));
    }

    protected static function crmEncryptedSearchAccessor(): \Closure
    {
        return fn ($r) => trim((($r->first_name ?? '') . ' ' . ($r->middle_name ?? '') . ' ' . ($r->last_name ?? '') . ' ' . ($r->maiden_name ?? '')));
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
