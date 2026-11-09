<?php

namespace VentureDrake\LaravelCrmFilament\Concerns\Forms;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Fluent;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\OrganizationService;
use VentureDrake\LaravelCrm\Services\PersonService;

/**
 * Shared "Contact" and "Organization" left-column Sections used by Lead +
 * Deal create/edit forms to mirror the core CRM layout.
 */
class LeadDealContactSection
{
    /**
     * Compose a person's display name (first + last), matching the
     * convention used by the contactColumn() Select's
     * getOptionLabelFromRecordUsing closure.
     */
    public static function personLabel(?Person $person): string
    {
        if ($person === null) {
            return '';
        }

        return trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
    }

    public static function contactColumn(): Section
    {
        return Section::make(__('laravel-crm-filament::labels.fields.contact'))
            ->schema([
                Forms\Components\Select::make('person_id')
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
                                ->label(__('laravel-crm-filament::labels.fields.first_name') ?: 'First Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('last_name')
                                ->label(__('laravel-crm-filament::labels.fields.last_name') ?: 'Last Name')
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
                                ->createOptionUsing(fn (array $data): int => SalesDetailsSection::createOrganizationFromModal($data)),
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
                    ->createOptionUsing(fn (array $data): int => SalesDetailsSection::createPersonFromModal($data)),
            ]);
    }

    public static function organizationColumn(): Section
    {
        return Section::make(__('laravel-crm-filament::labels.fields.organization'))
            ->schema([
                Forms\Components\Select::make('organization_id')
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
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('line1')
                            ->label(__('laravel-crm-filament::labels.contact.line1'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('line2')
                            ->label(__('laravel-crm-filament::labels.contact.line2'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('line3')
                            ->label(__('laravel-crm-filament::labels.contact.line3'))
                            ->maxLength(255),
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('city')
                                ->label(__('laravel-crm-filament::labels.contact.city'))
                                ->maxLength(100),
                            Forms\Components\TextInput::make('state')
                                ->label(__('laravel-crm-filament::labels.contact.state'))
                                ->maxLength(100),
                        ]),
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('code')
                                ->label(__('laravel-crm-filament::labels.contact.post_code'))
                                ->maxLength(20),
                            Forms\Components\TextInput::make('country')
                                ->label(__('laravel-crm-filament::labels.contact.country'))
                                ->maxLength(100),
                        ]),
                    ])
                    ->createOptionUsing(fn (array $data): int => SalesDetailsSection::createOrganizationFromModal($data)),
            ]);
    }
}
