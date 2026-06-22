<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\CreateUser;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\EditUser;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ListUsers;
use VentureDrake\LaravelCrmFilament\Resources\Users\Pages\ViewUser;

class UserResource extends Resource
{
    protected static ?string $slug = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Contacts';

    protected static ?int $navigationSort = 50;

    public static function getModel(): string
    {
        // Defer to host's configured user model so this works even when
        // the host extends App\Models\User from elsewhere.
        return config('auth.providers.users.model', User::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]),

            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->helperText('Leave blank when editing to keep the existing password.')
                ->columnSpanFull(),

            Forms\Components\Toggle::make('crm_access')
                ->label(__('laravel-crm-filament::labels.misc.crm_access'))
                ->helperText('Required for the user to reach the Filament panel.')
                ->default(true),

            Forms\Components\Select::make('roles')
                ->label(__('laravel-crm-filament::labels.fields.roles'))
                ->multiple()
                ->relationship('roles', 'name')
                ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('laravel-crm-filament::labels.fields.email'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('laravel-crm-filament::labels.fields.verified'))
                    ->date()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('crm_access')
                    ->label(__('laravel-crm-filament::labels.fields.crm_access'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('laravel-crm-filament::labels.fields.role'))
                    ->state(fn ($record) => $record->roles->first()?->name),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since(),
                Tables\Columns\TextColumn::make('last_online_at')
                    ->label(__('laravel-crm-filament::labels.fields.last_online'))
                    ->since()
                    ->placeholder('Never'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('crm_access')->label(__('laravel-crm-filament::labels.misc.has_crm_access')),
            ])
            ->recordActions([
                Actions\ViewAction::make()->button()->hiddenLabel(),
                Actions\EditAction::make()->button()->hiddenLabel(),
                Actions\DeleteAction::make()->button()->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema([
                    TextEntry::make('name')
                        ->label(__('laravel-crm-filament::labels.fields.name')),

                    TextEntry::make('email')
                        ->label(__('laravel-crm-filament::labels.fields.email')),

                    TextEntry::make('email_verified_at')
                        ->label(__('laravel-crm-filament::labels.fields.verified'))
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('crm_access')
                        ->label(__('laravel-crm-filament::labels.fields.crm_access'))
                        ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),

                    TextEntry::make('role')
                        ->label(__('laravel-crm-filament::labels.fields.role'))
                        ->state(fn ($record) => $record?->roles?->first()?->name),

                    TextEntry::make('created_at')
                        ->label(__('laravel-crm-filament::labels.fields.created'))
                        ->since(),

                    TextEntry::make('last_online_at')
                        ->label(__('laravel-crm-filament::labels.fields.last_online'))
                        ->since()
                        ->placeholder('Never'),
                ]),
        ])->columns(1);
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_users'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
