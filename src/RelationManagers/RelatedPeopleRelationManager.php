<?php

namespace VentureDrake\LaravelCrmFilament\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrmFilament\Concerns\Forms\SalesDetailsSection;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;

class RelatedPeopleRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedPeopleContacts';

    protected static ?string $title = 'Related people';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('person_id')
                ->label(__('laravel-crm-filament::labels.fields.contact'))
                ->options(function () {
                    $ownerId = $this->getOwnerRecord()?->id;
                    $tenant = \Filament\Facades\Filament::getTenant();

                    return Person::query()
                        ->when($ownerId, fn ($q) => $q->where('id', '!=', $ownerId))
                        ->when($tenant, fn ($q) => $q->where(fn ($sub) => $sub->where('team_id', $tenant->id)->orWhere('tenant_id', $tenant->id)->orWhereNull('team_id')))
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Person $person) => [
                            $person->id => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                        ])
                        ->filter()
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required()
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('related_name')
                    ->label(__('laravel-crm-filament::labels.fields.contact'))
                    ->state(fn (Contact $record) => $record->entityable?->name ?? '—')
                    ->url(fn (Contact $record) => $record->entityable instanceof Person
                        ? PersonResource::getUrl('view', ['record' => $record->entityable])
                        : null),

                Tables\Columns\TextColumn::make('related_email')
                    ->label(__('laravel-crm-filament::labels.fields.email'))
                    ->state(fn (Contact $record) => $record->entityable instanceof Person
                        ? $record->entityable->getPrimaryEmail()?->address
                        : null)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('related_phone')
                    ->label(__('laravel-crm-filament::labels.fields.phone'))
                    ->state(fn (Contact $record) => $record->entityable instanceof Person
                        ? $record->entityable->getPrimaryPhone()?->number
                        : null)
                    ->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(__('laravel-crm-filament::labels.actions.add_person'))
                    ->modalHeading(__('laravel-crm-filament::labels.actions.add_person'))
                    ->using(function (array $data): Contact {
                        $owner = $this->getOwnerRecord();
                        $target = Person::findOrFail($data['person_id']);

                        $tenant = \Filament\Facades\Filament::getTenant();
                        $tenantId = $tenant?->id;
                        if (! $tenantId && auth()->user()) {
                            $tenantId = auth()->user()->current_tenant_id ?? auth()->user()->tenant_id;
                        }

                        $contactA = Contact::firstOrCreate([
                            'team_id' => $tenantId,
                            'contactable_type' => $owner->getMorphClass(),
                            'contactable_id' => $owner->id,
                            'entityable_type' => $target->getMorphClass(),
                            'entityable_id' => $target->id,
                        ]);

                        Contact::firstOrCreate([
                            'team_id' => $tenantId,
                            'contactable_type' => $target->getMorphClass(),
                            'contactable_id' => $target->id,
                            'entityable_type' => $owner->getMorphClass(),
                            'entityable_id' => $owner->id,
                        ]);

                        return $contactA;
                    })
                    ->successNotification(
                        Notification::make()
                            ->title(__('laravel-crm-filament::labels.notifications.person_added'))
                            ->success()
                    ),
            ])
            ->actions([
                Actions\DeleteAction::make()
                    ->label(__('laravel-crm-filament::labels.actions.remove'))
                    ->modalHeading(__('laravel-crm-filament::labels.actions.remove_person'))
                    ->using(function (Contact $record): void {
                        Contact::query()
                            ->where('contactable_type', $record->entityable_type)
                            ->where('contactable_id', $record->entityable_id)
                            ->where('entityable_type', $record->contactable_type)
                            ->where('entityable_id', $record->contactable_id)
                            ->delete();

                        $record->delete();
                    })
                    ->successNotification(
                        Notification::make()
                            ->title(__('laravel-crm-filament::labels.notifications.person_removed'))
                            ->success()
                    ),
            ]);
    }
}
