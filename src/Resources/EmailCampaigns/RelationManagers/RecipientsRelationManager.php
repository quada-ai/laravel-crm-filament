<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\RelationManagers;

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
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('address')->label(__('laravel-crm-filament::labels.contact.email'))->searchable(),
                Tables\Columns\TextColumn::make('person.name')->label(__('laravel-crm-filament::labels.money.person'))->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray', 'sent' => 'success',
                        'failed' => 'danger', 'bounced' => 'danger',
                        'skipped' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('opens_count')->label(__('laravel-crm-filament::labels.campaign.opens'))->numeric()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('clicks_count')->label(__('laravel-crm-filament::labels.campaign.clicks'))->numeric()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('last_opened_at')->label(__('laravel-crm-filament::labels.campaign.last_opened'))->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('first_clicked_at')->label(__('laravel-crm-filament::labels.campaign.first_clicked'))->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('unsubscribed_at')->dateTime()->toggleable()->label(__('laravel-crm-filament::labels.campaign.unsubscribed')),
                Tables\Columns\TextColumn::make('bounce_status')
                    ->label(__('laravel-crm-filament::labels.campaign.bounce'))
                    ->state(fn ($record): string => in_array($record->status, ['bounced', 'failed'], true) ? ucfirst($record->status) : '—')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Bounced', 'Failed' => 'danger', default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending', 'sent' => 'Sent',
                        'failed' => 'Failed', 'bounced' => 'Bounced', 'skipped' => 'Skipped',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
