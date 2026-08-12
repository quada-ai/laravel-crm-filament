<?php

namespace VentureDrake\LaravelCrmFilament\Resources\People;

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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\ChatbotIntegration;
use App\Models\Conversation;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\ContactFieldsSchema;
use VentureDrake\LaravelCrmFilament\Concerns\ExportsCsv;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFieldEntries;
use VentureDrake\LaravelCrmFilament\Concerns\HasCrmCustomFields;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedGlobalSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasEncryptedSearch;
use VentureDrake\LaravelCrmFilament\Concerns\HasLabels;
use VentureDrake\LaravelCrmFilament\Concerns\TranslatableResource;
use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmActivitiesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmCallsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmFilesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmLunchesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmMeetingsRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmNotesRelationManager;
use VentureDrake\LaravelCrmFilament\RelationManagers\CrmTasksRelationManager;
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
    use TranslatableResource;
    use UsesExternalIdRouting;

    protected static string $resourceTranslationKey = 'person';
    protected static string $navigationGroupKey = 'contacts';

    protected static ?string $model = Person::class;

    protected static ?string $slug = 'people';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            $query->where($query->getModel()->getTable() . '.tenant_id', $tenant->getKey());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('first_name')
                    ->label(__('laravel-crm-filament::labels.fields.first_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label(__('laravel-crm-filament::labels.fields.last_name'))
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('middle_name')
                    ->label(__('laravel-crm-filament::labels.fields.middle_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('maiden_name')
                    ->label(__('laravel-crm-filament::labels.fields.maiden_name'))
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('company_name')
                    ->label(__('filament.resources.contacts.fields.company_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('business_field')
                    ->label(__('filament.resources.contacts.fields.business_field'))
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Forms\Components\TextInput::make('telegram_username')
                    ->label(__('filament.resources.contacts.fields.telegram_username'))
                    ->prefix('@')
                    ->maxLength(255),
                Forms\Components\TextInput::make('external_id')
                    ->label(__('filament.resources.contacts.fields.external_id'))
                    ->maxLength(255),
            ]),

            Grid::make(3)->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('laravel-crm-filament::labels.fields.title'))
                    ->maxLength(50),
                Forms\Components\Select::make('gender')
                    ->label(__('laravel-crm-filament::labels.fields.gender'))
                    ->options([
                        'male' => __('laravel-crm-filament::labels.gender.male'),
                        'female' => __('laravel-crm-filament::labels.gender.female'),
                        'other' => __('laravel-crm-filament::labels.gender.other'),
                    ]),
                Forms\Components\DatePicker::make('birthday')
                    ->label(__('laravel-crm-filament::labels.fields.birthday')),
            ]),

            Forms\Components\Textarea::make('description')
                ->label(__('laravel-crm-filament::labels.fields.description'))
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->options(fn () => \VentureDrake\LaravelCrmFilament\Support\UserOptions::get())
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
                Tables\Columns\ImageColumn::make('profile_avatar')
                    ->label(__('filament.resources.contacts.fields.profile_avatar') ?? 'Avatar')
                    ->circular()
                    ->disk('public')
                    ->state(fn (Person $record): ?string => $record->profile_avatar_url)
                    ->defaultImageUrl(fn () => asset('images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        if (blank($search)) {
                            return $query;
                        }

                        $term = mb_strtolower(trim((string) $search));
                        $terms = array_filter(explode(' ', $term));
                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;

                        $dbQuery = \Illuminate\Support\Facades\DB::table('crm_people')
                            ->whereNull('deleted_at');
                        if ($tenantId) {
                            $dbQuery->where('tenant_id', $tenantId);
                        }

                        // Fast SQL pre-filter for non-encrypted columns
                        $sqlMatchingIds = (clone $dbQuery)
                            ->where(function ($q) use ($term) {
                                $q->where('phone', 'like', "%{$term}%")
                                  ->orWhere('email', 'like', "%{$term}%")
                                  ->orWhere('company_name', 'like', "%{$term}%")
                                  ->orWhere('business_field', 'like', "%{$term}%")
                                  ->orWhere('telegram_username', 'like', "%{$term}%")
                                  ->orWhere('external_id', 'like', "%{$term}%");
                            })
                            ->pluck('id')
                            ->toArray();

                        // Raw DB fetch for name decryption without Eloquent model overhead
                        $rawPeople = $dbQuery
                            ->select(['id', 'first_name', 'middle_name', 'last_name', 'maiden_name'])
                            ->get();

                        $decryptedMatchingIds = [];

                        foreach ($rawPeople as $person) {
                            if (in_array($person->id, $sqlMatchingIds)) {
                                continue;
                            }

                            $firstName = $person->first_name ? self::decryptVal($person->first_name) : '';
                            $middleName = $person->middle_name ? self::decryptVal($person->middle_name) : '';
                            $lastName = $person->last_name ? self::decryptVal($person->last_name) : '';
                            $maidenName = $person->maiden_name ? self::decryptVal($person->maiden_name) : '';

                            $fullName = mb_strtolower(trim("{$firstName} {$middleName} {$lastName} {$maidenName}"));

                            if (empty($fullName)) {
                                continue;
                            }

                            if (str_contains($fullName, $term)) {
                                $decryptedMatchingIds[] = $person->id;
                                continue;
                            }

                            $allMatch = true;
                            foreach ($terms as $w) {
                                if (!str_contains($fullName, $w)) {
                                    $allMatch = false;
                                    break;
                                }
                            }
                            if ($allMatch && !empty($terms)) {
                                $decryptedMatchingIds[] = $person->id;
                            }
                        }

                        $allIds = array_unique(array_merge($sqlMatchingIds, $decryptedMatchingIds));

                        return $query->whereIn($query->getModel()->getQualifiedKeyName(), $allIds);
                    })
                    ->limit(40),

                Tables\Columns\TextColumn::make('labels.name')
                    ->label(__('laravel-crm-filament::labels.fields.labels'))
                    ->badge()
                    ->color(function ($state, $record) {
                        $label = $record?->labels?->firstWhere('name', $state);
                        $hex = $label?->hex;

                        if (! $hex) {
                            return 'gray';
                        }

                        return '#' . ltrim($hex, '#');
                    })
                    ->limitList(3),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('laravel-crm-filament::labels.fields.email'))
                    ->state(fn($record) => $record?->email ?: $record?->getPrimaryEmail()?->address),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('laravel-crm-filament::labels.fields.phone'))
                    ->state(fn($record) => $record?->phone ?: $record?->getPrimaryPhone()?->number),

                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('filament.resources.contacts.fields.company_name') ?? 'Company')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('business_field')
                    ->label(__('filament.resources.contacts.fields.business_field') ?? 'Business Field')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telegram_username')
                    ->label(__('filament.resources.contacts.fields.telegram_username') ?? 'Telegram')
                    ->formatStateUsing(fn ($state) => $state ? '@' . ltrim($state, '@') : null)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('open_deals_count')
                    ->label(__('laravel-crm-filament::labels.fields.open_deals'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lost_deals_count')
                    ->label(__('laravel-crm-filament::labels.fields.lost_deals'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('won_deals_count')
                    ->label(__('laravel-crm-filament::labels.fields.won_deals'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_activity')
                    ->label(__('laravel-crm-filament::labels.fields.next_activity'))
                    ->state(fn($record) => $record?->tasks()
                        ->whereNull('completed_at')
                        ->where('due_at', '>=', now())
                        ->orderBy('due_at')
                        ->first()?->due_at)
                    ->dateTime(),

                Tables\Columns\TextColumn::make('ownerUser.name')
                    ->label(__('laravel-crm-filament::labels.fields.owner'))
                    ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('laravel-crm-filament::labels.fields.created'))
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) use ($encrypted) {
                $query->withCount([
                    'deals as open_deals_count' => fn($q) => $q->whereNull('closed_at'),
                    'deals as lost_deals_count' => fn($q) => $q->where('closed_status', 'lost'),
                    'deals as won_deals_count' => fn($q) => $q->where('closed_status', 'won'),
                ]);

                if ($encrypted) {
                    $accessor = HasEncryptedSearch::modifyQuery(
                        fn($r) => trim(implode(' ', array_filter([
                            $r->first_name ?? null,
                            $r->middle_name ?? null,
                            $r->last_name ?? null,
                            $r->maiden_name ?? null,
                            $r->company_name ?? null,
                            $r->phone ?? null,
                            $r->email ?? null,
                            $r->telegram_username ?? null,
                        ])))
                    );
                    $accessor($query);
                }

                return $query;
            })
            ->recordActions([
                Action::make('startChat')
                    ->label(__('filament.resources.contacts.actions.start_chat') ?? 'Start Chat')
                    ->modalSubmitActionLabel(__('filament.resources.contacts.actions.start_chat') ?? 'Start Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->form(function (Person $record) {
                        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                        if (!$tenantId) {
                            return [];
                        }

                        $integrations = ChatbotIntegration::with('chatbot')
                            ->where('tenant_id', $tenantId)
                            ->where('channel_type', '!=', 'web')
                            ->get();

                        $options = [];
                        foreach ($integrations as $integration) {
                            $channel = ucfirst($integration->channel_type);
                            $detail = '';
                            try {
                                $creds = $integration->credentials;
                                if ($integration->channel_type === 'whatsapp') {
                                    $phone = $creds['display_phone_number']
                                        ?? $creds['phone_number']
                                        ?? $creds['phone']
                                        ?? $creds['phone_number_id']
                                        ?? null;
                                    $detail = $phone ? "({$phone})" : '';
                                } elseif ($integration->channel_type === 'telegram') {
                                    $detail = !empty($creds['bot_username']) ? "(@{$creds['bot_username']})" : '';
                                }
                            } catch (\Throwable $e) {
                                $detail = '';
                            }

                            $botName = $integration->chatbot?->name;
                            $labelParts = array_filter([$channel, $detail, $botName ? "[{$botName}]" : null]);
                            $options[$integration->id] = implode(' ', $labelParts);
                        }

                        return [
                            Forms\Components\Select::make('integration_id')
                                ->label(__('filament.resources.contacts.fields.select_integration') ?? 'Select Integration')
                                ->options($options)
                                ->required()
                                ->helperText(empty($options) ? __('filament.resources.contacts.helpers.no_integrations') ?? 'No integrations found' : null),
                        ];
                    })
                    ->action(function (Person $record, array $data) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if (!$tenant) {
                            return;
                        }

                        $integration = ChatbotIntegration::find($data['integration_id']);
                        if (!$integration) {
                            Notification::make()
                                ->title('Integration not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        $conversation = Conversation::where('tenant_id', $tenant->id)
                            ->where('contact_id', $record->id)
                            ->where(function ($q) use ($integration) {
                                $q->where('metadata->integration_id', $integration->id)
                                  ->orWhere('metadata->integration_id', (string) $integration->id)
                                  ->orWhere(function ($subQ) use ($integration) {
                                      $subQ->whereNull('metadata->integration_id')
                                           ->where('channel', $integration->channel_type);
                                  });
                            })
                            ->where('status', 'open')
                            ->first();

                        if (!$conversation) {
                            $contactPhone = $record->phone ?: $record->getPrimaryPhone()?->number;

                            if ($integration->channel_type === 'whatsapp') {
                                $phone = preg_replace('/[^0-9]/', '', (string) $contactPhone);

                                if (empty($phone)) {
                                    Notification::make()
                                        ->title(__('filament.resources.contacts.notifications.no_whatsapp_title') ?? 'No WhatsApp')
                                        ->body(__('filament.resources.contacts.notifications.invalid_phone_body') ?? 'Invalid Phone Number')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                            }

                            $externalUserId = match ($integration->channel_type) {
                                'whatsapp' => $contactPhone ? preg_replace('/[^0-9]/', '', $contactPhone) : ($record->external_id ?? (string) $record->id),
                                default => $record->external_id ?? $contactPhone ?? $record->email ?? (string) $record->id,
                            };

                            $integrationPhone = null;
                            if ($integration->channel_type === 'whatsapp') {
                                $creds = $integration->credentials;
                                $integrationPhone = $creds['display_phone_number'] ?? $creds['phone_number'] ?? $creds['phone'] ?? null;
                            }

                            $conversation = Conversation::create([
                                'tenant_id' => $tenant->id,
                                'chatbot_id' => $integration->chatbot_id,
                                'contact_id' => $record->id,
                                'external_user_id' => $externalUserId,
                                'channel' => $integration->channel_type,
                                'status' => 'open',
                                'is_escalated' => true,
                                'assigned_to' => auth()->id(),
                                'metadata' => array_filter([
                                    'integration_id' => $integration->id,
                                    'integration_phone' => $integrationPhone,
                                ]),
                            ]);
                        }

                        return redirect()->to(
                            route('filament.agent.pages.chat', [
                                'tenant' => $tenant->id,
                                'conversation' => $conversation->id,
                            ])
                        );
                    }),
                Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel(),
                Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    ExportsCsv::action(
                        columns: [
                            'First name' => fn($r) => $r->first_name,
                            'Last name' => fn($r) => $r->last_name,
                            'Organization' => fn($r) => optional($r->organization)->name,
                            'Owner' => fn($r) => optional($r->ownerUser)->name,
                            'Created' => fn($r) => $r->created_at,
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
                ->schema(fn(?Person $record) => array_merge(
                    static::personDetailEntries($record),
                    $record ? static::crmCustomFieldEntries($record, false) : [],
                )),

            Section::make(__('laravel-crm-filament::labels.sections.custom_fields'))
                ->schema(fn(?Person $record) => $record ? static::crmCustomFieldEntries($record, true) : [])
                ->hidden(function ($record): bool {
                    if (! $record instanceof Person) {
                        return true;
                    }

                    return ! $record->fields()
                        ->whereHas('field', fn($q) => $q->whereNotNull('field_group_id'))
                        ->exists();
                }),
        ])->columns(1);
    }

    /**
     * @return array<int, TextEntry>
     */
    protected static function personDetailEntries(?Person $record): array
    {
        $entries = [
            TextEntry::make('first_name')
                ->label(__('laravel-crm-filament::labels.fields.first_name')),

            TextEntry::make('middle_name')
                ->label(__('laravel-crm-filament::labels.fields.middle_name')),

            TextEntry::make('last_name')
                ->label(__('laravel-crm-filament::labels.fields.last_name')),

            TextEntry::make('gender')
                ->label(__('laravel-crm-filament::labels.fields.gender'))
                ->formatStateUsing(fn($state) => $state ? ucfirst((string) $state) : null),

            TextEntry::make('birthday')
                ->label(__('laravel-crm-filament::labels.fields.birthday')),

            TextEntry::make('description')
                ->label(__('laravel-crm-filament::labels.fields.description'))
                ->columnSpanFull(),
        ];

        if (! $record instanceof Person) {
            return $entries;
        }

        foreach ($record->phones as $i => $phone) {
            $typePrefix = $phone->type ? ucfirst($phone->type) . ' ' : '';
            $entries[] = TextEntry::make('phone_' . $i)
                ->label($typePrefix . __('laravel-crm-filament::labels.fields.phone'))
                ->state(trim(($phone->number ?? '') . ($phone->primary ? ' (Primary)' : '')));
        }

        foreach ($record->emails as $i => $email) {
            $typePrefix = $email->type ? ucfirst($email->type) . ' ' : '';
            $entries[] = TextEntry::make('email_' . $i)
                ->label($typePrefix . __('laravel-crm-filament::labels.fields.email'))
                ->state(trim(($email->address ?? '') . ($email->primary ? ' (Primary)' : '')));
        }

        foreach ($record->addresses as $i => $address) {
            $typePrefix = $address->addressType?->name ? ucfirst($address->addressType->name) . ' ' : '';
            $line = trim((string) static::formatAddressLine($address));
            if ($address->primary) {
                $line = trim($line . ' (Primary)');
            }
            $entries[] = TextEntry::make('address_' . $i)
                ->label($typePrefix . __('laravel-crm-filament::labels.fields.address'))
                ->state($line)
                ->columnSpanFull();
        }

        $entries[] = TextEntry::make('labels.name')
            ->label(__('laravel-crm-filament::labels.fields.labels'))
            ->badge()
            ->color(function ($state, $record) {
                $label = $record?->labels?->firstWhere('name', $state);
                $hex = $label?->hex;

                if (! $hex) {
                    return 'gray';
                }

                return '#' . ltrim($hex, '#');
            });

        $entries[] = TextEntry::make('ownerUser.name')
            ->label(__('laravel-crm-filament::labels.fields.owner'))
            ->placeholder(__('laravel-crm-filament::labels.misc.unallocated'));

        return $entries;
    }

    protected static function formatAddressLine($address): ?string
    {
        $parts = array_filter([
            $address->line1 ?? null,
            $address->line2 ?? null,
            $address->line3 ?? null,
            $address->city ?? null,
            $address->state ?? null,
            $address->code ?? null,
            $address->country ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
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
        $relations = [];

        if (class_exists(\App\Filament\Agent\Resources\Contacts\RelationManagers\ConversationsRelationManager::class)) {
            $relations[] = \App\Filament\Agent\Resources\Contacts\RelationManagers\ConversationsRelationManager::class;
        } elseif (class_exists('VentureDrake\LaravelCrmFilament\RelationManagers\ConversationsRelationManager')) {
            $relations[] = 'VentureDrake\LaravelCrmFilament\RelationManagers\ConversationsRelationManager';
        }

        return array_merge($relations, [
            CrmActivitiesRelationManager::class,
            CrmNotesRelationManager::class,
            CrmTasksRelationManager::class,
            CrmCallsRelationManager::class,
            CrmMeetingsRelationManager::class,
            CrmLunchesRelationManager::class,
            CrmFilesRelationManager::class,
        ]);
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
        return fn($r) => trim((($r->first_name ?? '') . ' ' . ($r->middle_name ?? '') . ' ' . ($r->last_name ?? '') . ' ' . ($r->maiden_name ?? '')));
    }

    public static function getRecordTitle(?Model $record): string | Htmlable | null
    {
        if (! $record) {
            return static::getModelLabel();
        }

        $composed = trim(implode(' ', array_filter([
            $record->first_name ?? null,
            $record->last_name ?? null,
        ])));

        return $composed !== '' ? $composed : ($record->name ?? static::getModelLabel());
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

    public static function backToIndexAction(): Action
    {
        return Action::make('backToIndex')
            ->label(__('laravel-crm-filament::labels.actions.back_to_people'))
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url(static::getUrl('index'));
    }

    protected static function decryptVal(?string $val): string
    {
        if (empty($val)) {
            return '';
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decrypt($val);
        } catch (\Throwable $e) {
            return $val;
        }
    }
}
