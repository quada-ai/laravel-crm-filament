<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClicksRelationManager extends RelationManager
{
    protected static string $relationship = 'clicks';

    protected static ?string $title = 'Clicks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_url')
            ->columns([
                Tables\Columns\TextColumn::make('recipient.phone.number')
                    ->label(__('laravel-crm-filament::labels.contact.phone'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient.person.name')
                    ->label(__('laravel-crm-filament::labels.money.person'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('original_url')
                    ->label(__('laravel-crm-filament::labels.campaign.url'))
                    ->limit(60)
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn ($record): ?string => $record->original_url),
                Tables\Columns\TextColumn::make('clicked_at')
                    ->label(__('laravel-crm-filament::labels.campaign.clicked_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient.clicks_count')
                    ->label(__('laravel-crm-filament::labels.campaign.recipient_clicks'))
                    ->numeric()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label(__('laravel-crm-filament::labels.campaign.ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('clicked_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('recipient')
                    ->label(__('laravel-crm-filament::labels.campaign.recipients'))
                    // NB: `phone` on SmsCampaignRecipient is a belongsTo relation (via phone_id
                    // FK to crm_phones), NOT a column. Filament's SelectFilter->relationship()
                    // requires a real column on the related table for its SELECT. Use `phone_id`
                    // (the real FK column) as the label column and derive the display via
                    // getOptionLabelFromRecordUsing so the dropdown still shows the actual
                    // phone number pulled through the phone relation.
                    ->relationship('recipient', 'phone_id', fn ($query, $livewire) => $query
                        ->where('sms_campaign_id', $livewire->getOwnerRecord()->getKey())
                        ->with('phone'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->phone?->number ?? '—')
                    ->preload(),
                Tables\Filters\Filter::make('original_url')
                    ->label(__('laravel-crm-filament::labels.campaign.url'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('laravel-crm-filament::labels.campaign.url'))
                            ->placeholder('https://...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] ?? null,
                            fn ($q, $v) => $q->where('original_url', 'like', '%' . $v . '%'),
                        );
                    }),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
