<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Forms;

/**
 * Reusable Filament schema sections for CRM contact fields
 * (phones, emails, addresses). Used on Person and Organization forms.
 *
 * The repeater item shape matches exactly what PersonService / OrganizationService
 * update helpers consume:
 *   phones     -> [{id, number, type, primary}]
 *   emails     -> [{id, address, type, primary}]
 *   addresses  -> [{id, line1, line2, line3, city, state, code, country, primary}]
 */
class ContactFieldsSchema
{
    /** @return array<Forms\Components\Repeater> */
    public static function phonesRepeater(string $name = 'phones'): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($name)
            ->label(__('laravel-crm-filament::labels.contact.phone_numbers'))
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('number')
                    ->label(__('laravel-crm-filament::labels.contact.number'))
                    ->required()
                    ->tel()
                    ->maxLength(50),
                Forms\Components\Select::make('type')
                    ->options([
                        'work' => 'Work',
                        'home' => 'Home',
                        'mobile' => 'Mobile',
                        'fax' => 'Fax',
                        'other' => 'Other',
                    ]),
                Forms\Components\Toggle::make('primary')
                    ->label(__('laravel-crm-filament::labels.fields.primary'))
                    ->default(false),
            ])
            ->columns(4)
            ->addActionLabel('Add phone')
            ->defaultItems(0)
            ->reorderable(false)
            ->columnSpanFull();
    }

    public static function emailsRepeater(string $name = 'emails'): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($name)
            ->label(__('laravel-crm-filament::labels.contact.email_addresses'))
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('address')
                    ->label(__('laravel-crm-filament::labels.contact.email'))
                    ->required()
                    ->email()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'work' => 'Work',
                        'home' => 'Home',
                        'other' => 'Other',
                    ]),
                Forms\Components\Toggle::make('primary')
                    ->label(__('laravel-crm-filament::labels.fields.primary'))
                    ->default(false),
            ])
            ->columns(4)
            ->addActionLabel('Add email')
            ->defaultItems(0)
            ->reorderable(false)
            ->columnSpanFull();
    }

    public static function addressesRepeater(string $name = 'addresses'): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($name)
            ->label(__('laravel-crm-filament::labels.contact.addresses'))
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('line1')->label(__('laravel-crm-filament::labels.contact.line1'))->maxLength(255),
                Forms\Components\TextInput::make('line2')->label(__('laravel-crm-filament::labels.contact.line2'))->maxLength(255),
                Forms\Components\TextInput::make('line3')->label(__('laravel-crm-filament::labels.contact.line3'))->maxLength(255),
                Forms\Components\TextInput::make('city')->label(__('laravel-crm-filament::labels.contact.city'))->maxLength(100),
                Forms\Components\TextInput::make('state')->label(__('laravel-crm-filament::labels.contact.state'))->maxLength(100),
                Forms\Components\TextInput::make('code')->label(__('laravel-crm-filament::labels.contact.post_code'))->maxLength(20),
                Forms\Components\TextInput::make('country')->label(__('laravel-crm-filament::labels.contact.country'))->maxLength(100),
                Forms\Components\Toggle::make('primary')->label(__('laravel-crm-filament::labels.fields.primary'))->default(false),
            ])
            ->columns(2)
            ->addActionLabel('Add address')
            ->defaultItems(0)
            ->reorderable(false)
            ->columnSpanFull();
    }
}
