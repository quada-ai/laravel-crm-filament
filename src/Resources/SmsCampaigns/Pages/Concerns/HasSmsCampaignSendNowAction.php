<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use VentureDrake\LaravelCrm\Jobs\SendSmsCampaignRecipient;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;

trait HasSmsCampaignSendNowAction
{
    protected function smsCampaignSendNowAction(): Action
    {
        return Action::make('sendNow')
            ->label(__('laravel-crm-filament::labels.actions.send_now'))
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Send campaign now')
            ->modalDescription(function (SmsCampaign $record): string {
                $count = $this->resolveSmsRecipientCount($record);

                return 'This will send ' . $count . ' SMS messages immediately.';
            })
            ->modalSubmitActionLabel('Send now')
            ->visible(fn (SmsCampaign $record) => in_array($record->status, ['draft', 'scheduled'], true))
            ->action(function (SmsCampaign $record): void {
                if ($record->status === 'draft') {
                    app(SmsCampaignService::class)->materialiseRecipients($record);
                }

                $record->update([
                    'status' => 'sending',
                    'scheduled_at' => $record->scheduled_at ?? now(),
                ]);

                $count = 0;
                $record->recipients()->where('status', 'pending')->get()->each(function ($recipient) use (&$count) {
                    SendSmsCampaignRecipient::dispatch($recipient);
                    $count++;
                });

                Notification::make()
                    ->title('Campaign send dispatched')
                    ->body($count . ' recipient job(s) queued')
                    ->success()
                    ->send();
            });
    }

    private function resolveSmsRecipientCount(SmsCampaign $record): int
    {
        if ($record->status === 'draft') {
            return (int) Phone::query()
                ->where('subscribed', true)
                ->whereNotNull('number')
                ->count();
        }

        return (int) $record->recipients()->where('status', 'pending')->count();
    }
}
