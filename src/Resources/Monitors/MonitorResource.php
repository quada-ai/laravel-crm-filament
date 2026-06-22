<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors;

use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Jobs\RunMonitorCheck;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\AuditsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\MonitorChecksRelationManager;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages\CreateMonitor;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages\EditMonitor;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages\ListMonitors;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages\ViewMonitor;

class MonitorResource extends Resource
{
    use UsesExternalIdRouting;

    protected static ?string $model = Monitor::class;

    protected static ?string $slug = 'monitors';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-signal';

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Monitoring';
    }

    public static function isEnabled(): bool
    {
        return LaravelCrmPlugin::get()->isModuleEnabled('monitoring');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.monitor_settings'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('laravel-crm-filament::labels.fields.type'))
                            ->options([
                                'http' => 'HTTP',
                                'https' => 'HTTPS',
                            ])
                            ->default('https')
                            ->required(),

                        Forms\Components\Select::make('method')
                            ->label(__('laravel-crm-filament::labels.fields.method'))
                            ->options([
                                'GET' => 'GET',
                                'POST' => 'POST',
                                'HEAD' => 'HEAD',
                                'PUT' => 'PUT',
                                'DELETE' => 'DELETE',
                                'PATCH' => 'PATCH',
                            ])
                            ->default('GET')
                            ->required(),
                    ]),

                    Forms\Components\TextInput::make('url')
                        ->label(__('laravel-crm-filament::labels.fields.url'))
                        ->required()
                        ->maxLength(1024)
                        ->columnSpanFull(),

                    Forms\Components\KeyValue::make('headers')
                        ->label(__('laravel-crm-filament::labels.fields.headers'))
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('interval')
                            ->label(__('laravel-crm-filament::labels.fields.interval'))
                            ->numeric()
                            ->minValue(1)
                            ->default(5),

                        Forms\Components\TextInput::make('timeout')
                            ->label(__('laravel-crm-filament::labels.fields.timeout'))
                            ->numeric()
                            ->minValue(1)
                            ->default(30),

                        Forms\Components\TextInput::make('expected_status_code')
                            ->label(__('laravel-crm-filament::labels.fields.expected_status_code'))
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(599)
                            ->default(200),
                    ]),

                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('perf_threshold_ms')
                            ->label(__('laravel-crm-filament::labels.fields.perf_threshold_ms'))
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('downtime_minutes_before_alert')
                            ->label(__('laravel-crm-filament::labels.fields.downtime_minutes_before_alert'))
                            ->numeric()
                            ->minValue(0),
                    ]),

                    Grid::make(3)->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('laravel-crm-filament::labels.fields.is_active'))
                            ->default(true),

                        Forms\Components\Toggle::make('uptime_enabled')
                            ->label(__('laravel-crm-filament::labels.fields.uptime_enabled'))
                            ->default(true),

                        Forms\Components\Toggle::make('ssl_enabled')
                            ->label(__('laravel-crm-filament::labels.fields.ssl_enabled'))
                            ->default(false),
                    ]),

                    Grid::make(2)->schema([
                        Forms\Components\Select::make('user_owner_id')
                            ->label(__('laravel-crm-filament::labels.fields.owner'))
                            ->relationship('ownerUser', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('user_assigned_id')
                            ->label(__('laravel-crm-filament::labels.fields.assigned_to'))
                            ->relationship('assignedToUser', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('url')
                    ->label(__('laravel-crm-filament::labels.fields.url'))
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn (?Model $record): ?string => $record?->url),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('laravel-crm-filament::labels.fields.type'))
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_status')
                    ->label(__('laravel-crm-filament::labels.fields.last_status'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'slow' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_response_time')
                    ->label(__('laravel-crm-filament::labels.fields.last_response_time'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label(__('laravel-crm-filament::labels.fields.last_checked_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('laravel-crm-filament::labels.fields.is_active'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('uptime_enabled')
                    ->label(__('laravel-crm-filament::labels.fields.uptime_enabled'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('ssl_enabled')
                    ->label(__('laravel-crm-filament::labels.fields.ssl_enabled'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('laravel-crm-filament::labels.fields.type'))
                    ->options([
                        'http' => 'HTTP',
                        'https' => 'HTTPS',
                    ]),

                Tables\Filters\SelectFilter::make('last_status')
                    ->label(__('laravel-crm-filament::labels.fields.last_status'))
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'slow' => 'Slow',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('laravel-crm-filament::labels.fields.is_active')),

                Tables\Filters\TernaryFilter::make('ssl_enabled')
                    ->label(__('laravel-crm-filament::labels.fields.ssl_enabled')),

                Tables\Filters\SelectFilter::make('user_owner_id')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->multiple()
                    ->relationship('ownerUser', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->requiresConfirmation(),
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
            MonitorChecksRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['monitor_id', 'name', 'url', 'host'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Monitor $record */
        return (string) ($record->name ?? $record->host ?? $record->url ?? $record->getKey());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Monitor $record */
        return array_filter([
            'ID' => $record->monitor_id,
            'URL' => $record->url,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitors::route('/'),
            'create' => CreateMonitor::route('/create'),
            'view' => ViewMonitor::route('/{record}'),
            'edit' => EditMonitor::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('laravel-crm-filament::labels.sections.details'))
                ->schema([
                    TextEntry::make('created_at')
                        ->label(__('laravel-crm-filament::labels.fields.created'))
                        ->since(),

                    TextEntry::make('monitor_id')
                        ->label(__('laravel-crm-filament::labels.fields.number'))
                        ->placeholder('—'),

                    TextEntry::make('name')
                        ->label(__('laravel-crm-filament::labels.fields.name'))
                        ->placeholder('—'),

                    TextEntry::make('description')
                        ->label(__('laravel-crm-filament::labels.fields.description'))
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('type')
                        ->label(__('laravel-crm-filament::labels.fields.type'))
                        ->badge(),

                    TextEntry::make('method')
                        ->label(__('laravel-crm-filament::labels.fields.method'))
                        ->badge(),

                    TextEntry::make('url')
                        ->label(__('laravel-crm-filament::labels.fields.url'))
                        ->columnSpanFull(),

                    TextEntry::make('host')
                        ->label(__('laravel-crm-filament::labels.fields.host'))
                        ->placeholder('—'),

                    TextEntry::make('expected_status_code')
                        ->label(__('laravel-crm-filament::labels.fields.expected_status_code')),

                    TextEntry::make('interval')
                        ->label(__('laravel-crm-filament::labels.fields.interval')),

                    TextEntry::make('timeout')
                        ->label(__('laravel-crm-filament::labels.fields.timeout')),

                    TextEntry::make('perf_threshold_ms')
                        ->label(__('laravel-crm-filament::labels.fields.perf_threshold_ms'))
                        ->placeholder('—'),

                    TextEntry::make('downtime_minutes_before_alert')
                        ->label(__('laravel-crm-filament::labels.fields.downtime_minutes_before_alert'))
                        ->placeholder('—'),

                    TextEntry::make('is_active')
                        ->label(__('laravel-crm-filament::labels.fields.is_active'))
                        ->state(fn (?Monitor $record): string => $record?->is_active
                            ? __('laravel-crm::lang.yes')
                            : __('laravel-crm::lang.no')),

                    TextEntry::make('uptime_enabled')
                        ->label(__('laravel-crm-filament::labels.fields.uptime_enabled'))
                        ->state(fn (?Monitor $record): string => $record?->uptime_enabled
                            ? __('laravel-crm::lang.yes')
                            : __('laravel-crm::lang.no')),

                    TextEntry::make('ssl_enabled')
                        ->label(__('laravel-crm-filament::labels.fields.ssl_enabled'))
                        ->state(fn (?Monitor $record): string => $record?->ssl_enabled
                            ? __('laravel-crm::lang.yes')
                            : __('laravel-crm::lang.no')),

                    TextEntry::make('ownerUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.owner'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),

                    TextEntry::make('assignedToUser.name')
                        ->label(__('laravel-crm-filament::labels.fields.assigned_to'))
                        ->placeholder(__('laravel-crm-filament::labels.misc.unallocated')),
                ]),

            Section::make(__('laravel-crm-filament::labels.sections.ssl'))
                ->schema([
                    TextEntry::make('ssl_status')
                        ->label(__('laravel-crm-filament::labels.fields.ssl_status'))
                        ->placeholder('—'),

                    TextEntry::make('ssl_issuer')
                        ->label(__('laravel-crm-filament::labels.fields.ssl_issuer'))
                        ->placeholder('—'),

                    TextEntry::make('ssl_expires_at')
                        ->label(__('laravel-crm-filament::labels.fields.ssl_expires_at'))
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('ssl_last_checked_at')
                        ->label(__('laravel-crm-filament::labels.fields.ssl_last_checked_at'))
                        ->dateTime()
                        ->since()
                        ->placeholder('—'),
                ])
                ->hidden(fn (?Monitor $record): bool => ! ($record?->ssl_enabled ?? false)),

            Section::make(__('laravel-crm-filament::labels.sections.status'))
                ->schema([
                    TextEntry::make('last_status')
                        ->label(__('laravel-crm-filament::labels.fields.last_status'))
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'up' => 'success',
                            'down' => 'danger',
                            'slow' => 'warning',
                            default => 'gray',
                        })
                        ->placeholder('—'),

                    TextEntry::make('last_response_time')
                        ->label(__('laravel-crm-filament::labels.fields.last_response_time'))
                        ->placeholder('—'),

                    TextEntry::make('last_status_code')
                        ->label(__('laravel-crm-filament::labels.fields.last_status_code'))
                        ->placeholder('—'),

                    TextEntry::make('last_checked_at')
                        ->label(__('laravel-crm-filament::labels.fields.last_checked_at'))
                        ->dateTime()
                        ->since()
                        ->placeholder('—'),

                    TextEntry::make('last_status_changed_at')
                        ->label(__('laravel-crm-filament::labels.fields.last_status_changed_at'))
                        ->dateTime()
                        ->since()
                        ->placeholder('—'),

                    TextEntry::make('down_since_at')
                        ->label(__('laravel-crm-filament::labels.fields.down_since_at'))
                        ->dateTime()
                        ->since()
                        ->placeholder('—'),
                ]),
        ])->columns(1);
    }

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_monitors'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    public static function runCheckNowAction(): Action
    {
        return Action::make('runCheckNow')
            ->label(__('laravel-crm-filament::labels.actions.run_check_now'))
            ->icon('heroicon-o-bolt')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function (Monitor $record): void {
                // dispatchSync is used (not dispatch) so the check runs immediately in the
                // current request regardless of the host's queue connection. Without it,
                // hosts on a database/redis queue would see the action return before the
                // check ran, and the admin would not get an immediate result row.
                RunMonitorCheck::dispatchSync($record->id);

                Notification::make()
                    ->title(__('laravel-crm-filament::labels.actions.run_check_now'))
                    ->success()
                    ->send();
            });
    }
}
