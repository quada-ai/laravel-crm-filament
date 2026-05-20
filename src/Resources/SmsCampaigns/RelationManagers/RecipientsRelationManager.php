<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\RelationManagers;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use VentureDrake\LaravelCrm\Jobs\SendSmsCampaignRecipient;

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
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resendToFailed')
                        ->label(__('laravel-crm-filament::labels.actions.resend_to_failed'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $recipient) {
                                if (! in_array($recipient->status, ['failed', 'bounced'], true)) {
                                    continue;
                                }
                                $recipient->update([
                                    'status' => 'pending',
                                    'error' => null,
                                ]);
                                SendSmsCampaignRecipient::dispatch($recipient);
                                $count++;
                            }

                            Notification::make()
                                ->title(__('laravel-crm-filament::labels.actions.resend_to_failed'))
                                ->body($count . ' recipient job(s) re-queued')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
