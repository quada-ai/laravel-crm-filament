<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Organizations;

use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Industry;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrmFilament\Concerns\ContactFieldsSchema;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedGlobalSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\CallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\FilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\MeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\NotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\TasksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\CreateOrganization;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\EditOrganization;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ListOrganizations;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\Pages\ViewOrganization;

class OrganizationResource extends Resource
{
    use HasCrmCustomFields;
    use HasEncryptedGlobalSearch;
    use HasLabels;

    protected static ?string $model = Organization::class;

    protected static ?string $slug = 'organizations';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 40;

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
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
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

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            TasksRelationManager::class,
            CallsRelationManager::class,
            MeetingsRelationManager::class,
            FilesRelationManager::class,
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
