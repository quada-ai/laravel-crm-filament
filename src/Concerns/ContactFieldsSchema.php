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
            ->label('Phone numbers')
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('number')
                    ->label('Number')
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
                    ->label('Primary')
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
            ->label('Email addresses')
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('address')
                    ->label('Email')
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
                    ->label('Primary')
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
            ->label('Addresses')
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('line1')->label('Line 1')->maxLength(255),
                Forms\Components\TextInput::make('line2')->label('Line 2')->maxLength(255),
                Forms\Components\TextInput::make('line3')->label('Line 3')->maxLength(255),
                Forms\Components\TextInput::make('city')->label('City')->maxLength(100),
                Forms\Components\TextInput::make('state')->label('State / Province')->maxLength(100),
                Forms\Components\TextInput::make('code')->label('Post code')->maxLength(20),
                Forms\Components\TextInput::make('country')->label('Country')->maxLength(100),
                Forms\Components\Toggle::make('primary')->label('Primary')->default(false),
            ])
            ->columns(2)
            ->addActionLabel('Add address')
            ->defaultItems(0)
            ->reorderable(false)
            ->columnSpanFull();
    }
}
