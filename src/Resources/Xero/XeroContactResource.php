<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Xero;

use BackedEnum;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ListXeroContacts;
use VentureDrake\LaravelCrmFilament\Resources\Xero\Pages\ViewXeroContact;

class XeroContactResource extends Resource
{
    protected static ?string $model = XeroContact::class;

    protected static ?string $slug = 'xero-contacts';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 91;

    public static function getNavigationGroup(): ?string
    {
        return LaravelCrmPlugin::get()->getNavigationGroup() ?? 'Integrations';
    }

    public static function getNavigationLabel(): string
    {
        return __('laravel-crm-filament::labels.xero.contacts');
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('xero_contact_details')
                ->heading(__('laravel-crm-filament::labels.xero.contact_details'))
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label(__('laravel-crm-filament::labels.fields.name')),
                    TextEntry::make('contact_id')
                        ->label(__('laravel-crm-filament::labels.xero.contact_id'))
                        ->copyable(),
                    TextEntry::make('organization.name')
                        ->label(__('laravel-crm-filament::labels.xero.linked_organization'))
                        ->placeholder('—'),
                    TextEntry::make('updated_at')
                        ->label(__('laravel-crm-filament::labels.xero.last_sync'))
                        ->dateTime()
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('laravel-crm-filament::labels.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_id')
                    ->label(__('laravel-crm-filament::labels.xero.contact_id'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('laravel-crm-filament::labels.xero.linked_organization'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('laravel-crm-filament::labels.xero.last_sync'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListXeroContacts::route('/'),
            'view' => ViewXeroContact::route('/{record}'),
        ];
    }
}
