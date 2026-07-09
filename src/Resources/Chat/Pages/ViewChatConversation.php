<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat\Pages;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Services\ChatService;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;

class ViewChatConversation extends ViewRecord
{
    protected static string $resource = ChatConversationResource::class;

    protected string $view = 'laravel-crm-filament::chat.thread';

    /**
     * Loaded ChatMessage rows the blade view renders. Updated on mount, after
     * an agent reply, and on every broadcast received over the conversation's
     * public channel.
     */
    public Collection $messageItems;

    /**
     * Bound to the inline reply textarea (wire:model="replyBody"). Posted via
     * sendInlineReply() — the same code path the modal-style reply header
     * action uses.
     */
    public string $replyBody = '';

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->refreshMessages();

        // Mark visitor messages as read by an agent as soon as the inbox
        // opens. Mirrors how a chat client would auto-acknowledge.
        try {
            app(ChatService::class)->markRead($this->record, 'visitor');
        } catch (\Throwable $e) {
            // swallow — read receipts are best-effort
        }
    }

    public function refreshMessages(): void
    {
        /** @var ChatConversation $record */
        $record = $this->record;
        $this->messageItems = $record
            ->messages()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Subscribes the Livewire component to the conversation's broadcast channel.
     * Requires Laravel Echo (and a configured driver like Pusher) in the host.
     * If Echo isn't set up, messages still post but the page won't auto-refresh.
     */
    public function getListeners(): array
    {
        $channel = 'crm-chat.' . $this->record->external_id;

        return [
            "echo:{$channel},.chat.message" => 'refreshMessages',
        ];
    }

    /**
     * Inline reply form submit handler. Routes through the same code path as
     * the header "Reply" action (`ChatService::sendAgentMessage`) so the
     * ChatMessageSent broadcast / observer pipeline fires identically.
     */
    public function sendInlineReply(): void
    {
        $body = trim($this->replyBody);
        if ($body === '') {
            throw ValidationException::withMessages([
                'replyBody' => __('laravel-crm-filament::labels.fields.message') . ' is required.',
            ]);
        }

        /** @var ChatConversation $record */
        $record = $this->record;
        if ($record->status === 'closed') {
            Notification::make()
                ->title('Conversation is closed')
                ->danger()
                ->send();

            return;
        }

        app(ChatService::class)->sendAgentMessage(
            $record,
            (int) auth()->id(),
            $body,
        );

        app(ChatService::class)->markRead($record, 'visitor');
        if ($record->status === 'open') {
            $record->update(['status' => 'pending']);
        }

        $this->replyBody = '';
        $this->refreshMessages();
        Notification::make()->title('Reply sent')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatConversationResource::backToIndexAction(),
            Actions\Action::make('reopen')
                ->label(__('laravel-crm-filament::labels.actions.reopen'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (ChatConversation $record) => $record->status !== 'open')
                ->action(function (ChatConversation $record, $livewire): void {
                    $record->update(['status' => 'open', 'closed_at' => null]);
                    $livewire->refreshMessages();
                    Notification::make()->title('Conversation reopened')->success()->send();
                }),
            ChatConversationResource::convertToLeadAction(),
            ChatConversationResource::closeAction(),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }
}
