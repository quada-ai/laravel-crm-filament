<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone.number')
            ->columns([
                Tables\Columns\TextColumn::make('phone.number')->label(__('laravel-crm-filament::labels.contact.phone'))->searchable(),
                Tables\Columns\TextColumn::make('person.name')->label(__('laravel-crm-filament::labels.money.person'))->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray', 'sent' => 'info', 'delivered' => 'success',
                        'failed' => 'danger', 'bounced' => 'danger',
                        'skipped' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('delivered_count_state')
                    ->label(__('laravel-crm-filament::labels.money.delivered'))
                    ->state(fn ($record): int => $record->status === 'delivered' ? 1 : 0)
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('clicks_count')->label(__('laravel-crm-filament::labels.campaign.clicks'))->numeric()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('delivered_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('unsubscribed_at')->dateTime()->toggleable()->label(__('laravel-crm-filament::labels.campaign.unsubscribed')),
                Tables\Columns\TextColumn::make('clicksend_message_id')
                    ->label(__('laravel-crm-filament::labels.campaign.message_id'))
                    ->copyable()
                    ->copyMessage('Message ID copied')
                    ->limit(20)
                    ->toggleable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending', 'sent' => 'Sent', 'delivered' => 'Delivered',
                        'failed' => 'Failed', 'bounced' => 'Bounced', 'skipped' => 'Skipped',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
