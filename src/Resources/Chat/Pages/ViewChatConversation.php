<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat\Pages;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Services\ChatService;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;

class ViewChatConversation extends ViewRecord
{
    protected static string $resource = ChatConversationResource::class;

    protected string $view = 'laravel-crm-filament::chat.thread';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (ChatConversation $record) => $record->status === 'open')
                ->schema([
                    Textarea::make('body')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, ChatConversation $record): void {
                    app(ChatService::class)->sendAgentMessage($record, auth()->user(), $data['body']);
                    Notification::make()->title('Reply sent')->success()->send();
                }),
            Actions\Action::make('close')
                ->label('Close conversation')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (ChatConversation $record) => $record->status === 'open')
                ->action(function (ChatConversation $record): void {
                    app(ChatService::class)->close($record);
                    Notification::make()->title('Closed')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
