<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages;

use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrm\Sms\SmsCampaignMessage;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages\Concerns\HasSmsCampaignSendNowAction;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Widgets\SmsCampaignSendsOverTimeChart;

class ViewSmsCampaign extends ViewRecord
{
    use HasSmsCampaignSendNowAction;

    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (SmsCampaign $record) => $record->isEditable()),
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn (SmsCampaign $record) => 'Preview: ' . $record->name)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(function (SmsCampaign $record) {
                    $rendered = SmsCampaignMessage::renderPreview($record->body ?? '');
                    $segments = SmsCampaignMessage::segmentCount($record->body ?? '');

                    return new HtmlString(
                        '<div class="text-sm text-gray-500 mb-2">' . $segments . ' SMS segment(s)</div>' .
                        '<pre class="text-sm whitespace-pre-wrap font-sans">' . e($rendered) . '</pre>'
                    );
                }),
            $this->smsCampaignSendNowAction(),
            Actions\Action::make('schedule')
                ->label('Schedule')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->visible(fn (SmsCampaign $record) => $record->isEditable())
                ->schema([
                    DateTimePicker::make('scheduled_at')
                        ->label('Send at')
                        ->required(),
                ])
                ->action(function (array $data, SmsCampaign $record): void {
                    app(SmsCampaignService::class)->schedule($record, $data['scheduled_at']);
                    Notification::make()->title('Campaign scheduled')->success()->send();
                }),
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (SmsCampaign $record) => $record->isCancellable())
                ->action(function (SmsCampaign $record): void {
                    app(SmsCampaignService::class)->cancel($record);
                    Notification::make()->title('Campaign cancelled')->success()->send();
                }),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SmsCampaignSendsOverTimeChart::class,
        ];
    }
}
