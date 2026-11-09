<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Fluent;
use VentureDrake\LaravelCrm\Models\Order as OrderModel;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;

/**
 * Shared "Details" left-column Section used by Quote, Order, Invoice, and
 * PurchaseOrder create/edit forms. Person + Organization Selects are
 * always present; remaining scalar fields toggle via `$opts`.
 */
class SalesDetailsSection
{
    public static function make(array $opts = []): Section
    {
        $opts = array_merge([
            'title' => false,
            'description' => true,
            'reference' => true,
            'currency' => true,
            'issueDateKey' => null,
            'expiryDateKey' => null,
            'terms' => true,
            'stage' => false,
            'owner' => false,
            'labels' => false,
            'labelsField' => null,
            'customFields' => null,
            'orderLink' => false,
        ], $opts);

        $components = [
            self::personSelect(),
            self::organizationSelect(),
        ];

        if ($opts['orderLink']) {
            array_unshift($components, self::orderSelect());
        }

        if ($opts['title']) {
            $components[] = Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255);
        }

        if ($opts['description']) {
            $components[] = Forms\Components\Textarea::make('description')
                ->rows(5)
                ->columnSpanFull();
        }

        if ($opts['reference'] || $opts['currency']) {
            $row = [];
            if ($opts['reference']) {
                $row[] = Forms\Components\TextInput::make('reference')
                    ->label(__('laravel-crm-filament::labels.fields.reference'))
                    ->maxLength(100);
            }
            if ($opts['currency']) {
                $row[] = Forms\Components\Select::make('currency')
                    ->label(__('laravel-crm-filament::labels.fields.currency'))
                    ->options(fn () => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies())
                    ->searchable()
                    ->default(config('laravel-crm.default_currency', 'USD'));
            }
            $components[] = Grid::make(2)->schema($row);
        }

        $dateRow = [];
        if ($opts['issueDateKey']) {
            $dateRow[] = Forms\Components\DatePicker::make($opts['issueDateKey'])
                ->label(__('laravel-crm-filament::labels.money.issue_date'));
        }
        if ($opts['expiryDateKey']) {
            $label = match ($opts['expiryDateKey']) {
                'expire_at', 'expiry_date' => 'laravel-crm-filament::labels.money.expiry_date',
                'due_date' => 'laravel-crm-filament::labels.money.due_date',
                'delivery_date' => 'laravel-crm-filament::labels.money.delivery_date',
                default => 'laravel-crm-filament::labels.money.expiry_date',
            };
            $dateRow[] = Forms\Components\DatePicker::make($opts['expiryDateKey'])
                ->label(__($label));
        }
        if ($dateRow !== []) {
            $components[] = Grid::make(2)->schema($dateRow);
        }

        if ($opts['terms']) {
            $components[] = Forms\Components\Textarea::make('terms')
                ->rows(5)
                ->columnSpanFull();
        }

        if ($opts['stage']) {
            $components[] = Forms\Components\Select::make('pipeline_stage_id')
                ->label(__('laravel-crm-filament::labels.sales.pipeline_stage'))
                ->options(function ($get) {
                    $pipelineId = $get('pipeline_id');
                    if ($pipelineId) {
                        return PipelineStage::query()->where('pipeline_id', $pipelineId)->orderBy('order')->pluck('name', 'id');
                    }

                    return PipelineStage::query()->orderBy('order')->pluck('name', 'id');
                })
                ->searchable()
                ->preload();
        }

        if ($opts['owner']) {
            $components[] = Forms\Components\Select::make('user_owner_id')
                ->label(__('laravel-crm-filament::labels.fields.owner'))
                ->relationship('ownerUser', 'name')
                ->searchable()
                ->preload();
        }

        if ($opts['labels'] && is_callable($opts['labelsField'])) {
            $components[] = ($opts['labelsField'])();
        }

        if ($opts['customFields'] !== null) {
            $components[] = $opts['customFields'];
        }

        return Section::make(__('laravel-crm-filament::labels.sections.details'))
            ->schema($components);
    }

    public static function createPersonFromModal(array $data): int
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $tenantId = $tenant?->id;

        if (! $tenantId && request()->header('referer')) {
            $refererPath = parse_url(request()->header('referer'), PHP_URL_PATH);
            $refParts = explode('/', ltrim((string) $refererPath, '/'));
            if (count($refParts) >= 2 && ($refParts[0] === 'agent' || $refParts[0] === 'agentCRM') && is_numeric($refParts[1])) {
                $tenantId = (int) $refParts[1];
            }
        }

        if (! $tenantId) {
            $routeTenant = request()->route('tenant');
            $tenantId = $routeTenant instanceof \Illuminate\Database\Eloquent\Model ? $routeTenant->getKey() : $routeTenant;
        }

        if (! $tenantId && auth()->user()) {
            $tenantId = auth()->user()->current_tenant_id ?? auth()->user()->tenant_id;
        }

        if (! empty($tenantId) && is_numeric($tenantId)) {
            $tenantId = (int) $tenantId;
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = !empty($data['phone']) ? trim((string) $data['phone']) : null;
        $email = !empty($data['email']) ? trim((string) $data['email']) : null;
        $companyName = !empty($data['company_name']) ? trim((string) $data['company_name']) : null;
        $organizationId = !empty($data['organization_id']) ? (int) $data['organization_id'] : null;
        $userId = auth()->id() ?: 1;

        $person = Person::create([
            'external_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'first_name' => $firstName,
            'last_name' => $lastName ?: null,
            'user_owner_id' => $userId,
            'user_created_id' => $userId,
            'tenant_id' => $tenantId,
            'team_id' => $tenantId,
            'company_name' => $companyName,
            'organization_id' => $organizationId,
            'phone' => $phone,
            'email' => $email,
            'source' => 'manual',
        ]);

        \Illuminate\Support\Facades\DB::table('crm_people')
            ->where('id', $person->id)
            ->update(array_filter([
                'tenant_id' => $tenantId,
                'team_id' => $tenantId,
                'company_name' => $companyName,
                'organization_id' => $organizationId,
                'phone' => $phone,
                'email' => $email,
                'source' => 'manual',
                'user_owner_id' => $userId,
                'user_created_id' => $userId,
            ], fn ($v) => !is_null($v)));

        if ($phone) {
            $person->phones()->create([
                'external_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'number' => $phone,
                'type' => 'mobile',
                'primary' => 1,
            ]);
        }

        if ($email) {
            $person->emails()->create([
                'external_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'address' => $email,
                'type' => 'work',
                'primary' => 1,
            ]);
        }

        if ($organizationId && ($org = Organization::find($organizationId))) {
            app(PersonService::class)->linkToOrganization($person, $org);
        }

        return (int) $person->getKey();
    }

    public static function createOrganizationFromModal(array $data): int
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $tenantId = $tenant?->id;

        if (! $tenantId && request()->header('referer')) {
            $refererPath = parse_url(request()->header('referer'), PHP_URL_PATH);
            $refParts = explode('/', ltrim((string) $refererPath, '/'));
            if (count($refParts) >= 2 && ($refParts[0] === 'agent' || $refParts[0] === 'agentCRM') && is_numeric($refParts[1])) {
                $tenantId = (int) $refParts[1];
            }
        }

        if (! $tenantId && auth()->user()) {
            $tenantId = auth()->user()->current_tenant_id ?? auth()->user()->tenant_id;
        }

        $request = new Fluent([
            'organization_name' => $data['name'] ?? null,
            'line1' => $data['line1'] ?? null,
            'suburb' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'user_owner_id' => auth()->id() ?: 1,
        ]);

        $org = app(OrganizationService::class)->createFromRelated($request);
        if ($tenantId) {
            \Illuminate\Support\Facades\DB::table('crm_organizations')
                ->where('id', $org->id)
                ->update([
                    'team_id' => $tenantId,
                ]);
        }

        return (int) $org->getKey();
    }

    protected static function personSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('person_id')
            ->label(__('laravel-crm-filament::labels.fields.contact'))
            ->relationship('person', 'first_name', modifyQueryUsing: function ($query) {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant) {
                    $query->where(function ($q) use ($tenant) {
                        $q->where('team_id', $tenant->id)
                            ->orWhere('tenant_id', $tenant->id)
                            ->orWhereNull('team_id');
                    });
                }

                return $query;
            })
            ->getOptionLabelFromRecordUsing(function (Person $r) {
                $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                if (!empty($r->phone)) {
                    $name .= ' (' . $r->phone . ')';
                }
                return $name ?: ('#' . $r->id);
            })
            ->searchable(['first_name', 'last_name', 'middle_name', 'maiden_name', 'phone', 'email', 'company_name'])
            ->preload()
            ->nullable()
            ->createOptionForm([
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label(__('laravel-crm-filament::labels.fields.first_name') ?: 'First name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->label(__('laravel-crm-filament::labels.fields.last_name') ?: 'Last name')
                        ->maxLength(255),
                ]),
                Grid::make(2)->schema([
                    Forms\Components\Select::make('organization_id')
                        ->label(__('laravel-crm-filament::labels.fields.organization') ?: 'Organization')
                        ->relationship('organization', 'name', modifyQueryUsing: function ($query) {
                            $tenant = \Filament\Facades\Filament::getTenant();
                            if ($tenant) {
                                $query->where(function ($q) use ($tenant) {
                                    $q->where('team_id', $tenant->id)->orWhereNull('team_id');
                                });
                            }

                            return $query;
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label(__('laravel-crm-filament::labels.fields.name') ?: 'Name')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(fn (array $data): int => self::createOrganizationFromModal($data)),
                    Forms\Components\TextInput::make('company_name')
                        ->label(__('filament.resources.contacts.fields.company_name') ?: 'Company Name')
                        ->maxLength(255),
                ]),
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label(__('laravel-crm-filament::labels.fields.phone') ?: 'Phone')
                        ->tel()
                        ->maxLength(50),
                    Forms\Components\TextInput::make('email')
                        ->label(__('laravel-crm-filament::labels.fields.email') ?: 'Email')
                        ->email()
                        ->maxLength(255),
                ]),
            ])
            ->createOptionUsing(fn (array $data): int => self::createPersonFromModal($data));
    }

    protected static function organizationSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('organization_id')
            ->label(__('laravel-crm-filament::labels.fields.organization'))
            ->relationship('organization', 'name', modifyQueryUsing: function ($query) {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant) {
                    $query->where(function ($q) use ($tenant) {
                        $q->where('team_id', $tenant->id)->orWhereNull('team_id');
                    });
                }

                return $query;
            })
            ->searchable(['name'])
            ->preload()
            ->nullable()
            ->createOptionForm([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('line1')
                    ->label(__('laravel-crm-filament::labels.contact.line1'))
                    ->maxLength(255),
                Grid::make(2)->schema([
                    Forms\Components\TextInput::make('city')
                        ->label(__('laravel-crm-filament::labels.contact.city'))
                        ->maxLength(100),
                    Forms\Components\TextInput::make('country')
                        ->label(__('laravel-crm-filament::labels.contact.country'))
                        ->maxLength(100),
                ]),
            ])
            ->createOptionUsing(fn (array $data): int => self::createOrganizationFromModal($data));
    }

    protected static function orderSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('order_id')
            ->label(__('laravel-crm-filament::labels.fields.order'))
            ->options(fn () => OrderModel::query()->orderByDesc('id')->limit(50)->get()->mapWithKeys(fn ($o) => [$o->id => $o->order_id])->all())
            ->searchable()
            ->preload();
    }
}
