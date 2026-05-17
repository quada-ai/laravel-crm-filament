<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;

class ViewEmailCampaign extends ViewRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (EmailCampaign $record) => $record->isEditable()),
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn (EmailCampaign $record) => 'Preview: '.$record->name)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (EmailCampaign $record) => new \Illuminate\Support\HtmlString(\VentureDrake\LaravelCrm\Mail\EmailCampaignMessage::renderPreview($record->body ?? '', $record->preview_text ?? '', $record->team_id))),
            Actions\Action::make('schedule')
                ->label('Schedule')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->visible(fn (EmailCampaign $record) => $record->isEditable())
                ->schema([
                    DateTimePicker::make('scheduled_at')
                        ->label('Send at')
                        ->required(),
                ])
                ->action(function (array $data, EmailCampaign $record): void {
                    app(EmailCampaignService::class)->schedule($record, $data['scheduled_at']);
                    Notification::make()->title('Campaign scheduled')->success()->send();
                }),
            Actions\Action::make('cancel')
                ->label('Cancel')
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
}
