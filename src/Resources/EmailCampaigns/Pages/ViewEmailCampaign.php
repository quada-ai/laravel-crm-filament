<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Mail\EmailCampaignMessage;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages\Concerns\HasEmailCampaignSendNowAction;
use VentureDrake\LaravelCrmFilament\Widgets\EmailCampaignSendsOverTimeChart;

class ViewEmailCampaign extends ViewRecord
{
    use HasEmailCampaignSendNowAction;

    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (EmailCampaign $record) => $record->isEditable()),
            Actions\Action::make('preview')
                ->label(__('laravel-crm-filament::labels.actions.preview'))
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn (EmailCampaign $record) => 'Preview: ' . $record->name)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (EmailCampaign $record) => new HtmlString(EmailCampaignMessage::renderPreview($record->body ?? '', $record->preview_text ?? '', $record->team_id))),
            $this->emailCampaignSendNowAction(),
            Actions\Action::make('schedule')
                ->label(__('laravel-crm-filament::labels.actions.schedule'))
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->visible(fn (EmailCampaign $record) => $record->isEditable())
                ->schema([
                    DateTimePicker::make('scheduled_at')
                        ->label(__('laravel-crm-filament::labels.campaign.send_at'))
                        ->required(),
                ])
                ->action(function (array $data, EmailCampaign $record): void {
                    app(EmailCampaignService::class)->schedule($record, $data['scheduled_at']);
                    Notification::make()->title('Campaign scheduled')->success()->send();
                }),
            Actions\Action::make('cancel')
                ->label(__('laravel-crm-filament::labels.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (EmailCampaign $record) => $record->isCancellable())
                ->action(function (EmailCampaign $record): void {
                    app(EmailCampaignService::class)->cancel($record);
                    Notification::make()->title('Campaign cancelled')->success()->send();
                }),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            EmailCampaignSendsOverTimeChart::class,
        ];
    }
}
