<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Organizations;

use App\Models\User;
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
use VentureDrake\LaravelCrm\Models\Industry;
use VentureDrake\LaravelCrm\Models\Organization;
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
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\CreateOrganization;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\EditOrganization;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ViewOrganization;

class OrganizationResource extends Resource
{
    use HasCrmCustomFieldEntries;
    use HasCrmCustomFields;
    use HasEncryptedGlobalSearch;
    use HasLabels;
    use UsesExternalIdRouting;

    protected static ?string $model = Organization::class;

    protected static ?string $slug = 'organizations';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Contacts';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('vat_number')->maxLength(50),
                Forms\Components\TextInput::make('number_of_employees')->numeric(),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('annual_revenue')->numeric(),
                Forms\Components\TextInput::make('total_money_raised')->numeric(),
            ]),

            Forms\Components\Select::make('industry_id')
                ->label(__('laravel-crm-filament::labels.money.industry'))
                ->options(fn () => Industry::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('linkedin')
                ->url()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            static::labelsField(),

            ContactFieldsSchema::phonesRepeater(),
            ContactFieldsSchema::emailsRepeater(),
            ContactFieldsSchema::addressesRepeater(),
        ];

        if ($customFields = static::crmCustomFieldsSection(Organization::class)) {
            $components[] = $customFields;
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $encrypted = config('laravel-crm.encrypt_db_fields', false);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable(! $encrypted)
                    ->searchable(! $encrypted)
                    ->limit(60),

                Tables\Columns\TextColumn::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('number_of_employees')
                    ->label(__('laravel-crm-filament::labels.money.employees'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->when(
                $encrypted,
                fn (Table $t) => $t->modifyQueryUsing(
                    HasEncryptedSearch::modifyQuery(fn ($r) => $r->name ?? '')
                )
            )
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    ExportsCsv::action(
                        columns: [
                            'Name' => fn ($r) => $r->name,
                            'VAT' => fn ($r) => $r->vat,
                            'Employees' => fn ($r) => $r->employees,
                            'Revenue' => fn ($r) => $r->revenue,
                            'Created' => fn ($r) => $r->created_at,
                        ],
                        filename: 'organizations',
                    ),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.identity'))
                ->schema(fn (?Organization $record) => array_merge([
                    TextEntry::make('name')
                        ->label(__('laravel-crm-filament::labels.fields.name')),

                    TextEntry::make('industry.name')
                        ->label(__('laravel-crm-filament::labels.money.industry')),

                    TextEntry::make('number_of_employees')
                        ->label(__('laravel-crm-filament::labels.money.employees')),

                    TextEntry::make('annual_revenue')
                        ->label(__('laravel-crm-filament::labels.money.revenue'))
                        ->money(fn ($record) => config('laravel-crm.default_currency', 'USD')),
                ], $record ? static::crmCustomFieldEntries($record, false) : [])),

            Section::make(__('laravel-crm-filament::labels.sections.contact'))
                ->schema([
                    TextEntry::make('addresses')
                        ->label(__('laravel-crm-filament::labels.fields.addresses'))
                        ->state(fn ($record) => $record instanceof Organization ? static::formatAddresses($record) : null)
                        ->columnSpanFull(),
                ]),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn (?Organization $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Organization) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn ($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),
        ])->columns(1);
    }

    protected static function formatAddresses(Organization $record): ?string
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
        return ['name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->name ?? '');
    }

    protected static function crmEncryptedSearchAccessor(): \Closure
    {
        return fn ($r) => (string) ($r->name ?? '');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'view' => ViewOrganization::route('/{record}'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
