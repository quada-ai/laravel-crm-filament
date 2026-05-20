<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use VentureDrake\LaravelCrm\Jobs\SendEmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;

trait HasEmailCampaignSendNowAction
{
    protected function emailCampaignSendNowAction(): Action
    {
        return Action::make('sendNow')
            ->label('Send now')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Send campaign now')
            ->modalDescription(function (EmailCampaign $record): string {
                $count = $this->resolveEmailRecipientCount($record);

                return 'This will send ' . $count . ' emails immediately.';
            })
            ->modalSubmitActionLabel('Send now')
            ->visible(fn (EmailCampaign $record) => in_array($record->status, ['draft', 'scheduled'], true))
            ->action(function (EmailCampaign $record): void {
                if ($record->status === 'draft') {
                    app(EmailCampaignService::class)->materialiseRecipients($record);
                }

                $record->update([
                    'status' => 'sending',
                    'scheduled_at' => $record->scheduled_at ?? now(),
                ]);

                $count = 0;
                $record->recipients()->where('status', 'pending')->get()->each(function ($recipient) use (&$count) {
                    SendEmailCampaignRecipient::dispatch($recipient);
                    $count++;
                });

                Notification::make()
                    ->title('Campaign send dispatched')
                    ->body($count . ' recipient job(s) queued')
                    ->success()
                    ->send();
            });
    }

    private function resolveEmailRecipientCount(EmailCampaign $record): int
    {
        if ($record->status === 'draft') {
            return (int) Email::query()
                ->where('subscribed', true)
                ->whereNotNull('address')
                ->distinct('address')
                ->count('address');
        }

        return (int) $record->recipients()->where('status', 'pending')->count();
    }
}
