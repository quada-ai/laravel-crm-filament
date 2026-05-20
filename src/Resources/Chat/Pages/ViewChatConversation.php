<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat\Pages;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
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

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->refreshMessages();
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label(__('laravel-crm-filament::labels.actions.reply'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (ChatConversation $record) => $record->status === 'open')
                ->schema([
                    Textarea::make('body')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, ChatConversation $record, $livewire): void {
                    app(ChatService::class)->sendAgentMessage($record, auth()->user(), $data['body']);
                    $livewire->refreshMessages();
                    Notification::make()->title('Reply sent')->success()->send();
                }),
            Actions\Action::make('close')
                ->label(__('laravel-crm-filament::labels.actions.close_conversation'))
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (ChatConversation $record) => $record->status === 'open')
                ->action(function (ChatConversation $record): void {
                    app(ChatService::class)->close($record);
                    Notification::make()->title('Closed')->success()->send();
                }),
            Actions\Action::make('convertToLead')
                ->label(__('laravel-crm-filament::labels.actions.convert_to_lead'))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn (ChatConversation $record) => ! $record->lead_id)
                ->action(function (ChatConversation $record): void {
                    $visitor = $record->visitor;
                    $person = $visitor?->person;

                    if (! $person && ($visitor?->name || $visitor?->email)) {
                        $parts = $visitor->name ? explode(' ', trim($visitor->name), 2) : [];
                        $person = Person::create([
                            'external_id' => Uuid::uuid4()->toString(),
                            'first_name' => $parts[0] ?? null,
                            'last_name' => $parts[1] ?? null,
                            'user_created_id' => auth()->id(),
                            'user_updated_id' => auth()->id(),
                        ]);
                        if ($visitor->email) {
                            $person->emails()->create([
                                'address' => $visitor->email,
                                'primary' => true,
                                'user_created_id' => auth()->id(),
                                'user_updated_id' => auth()->id(),
                            ]);
                        }
                        $visitor->update(['person_id' => $person->id]);
                    }

                    $title = $visitor?->name
                        ? 'Chat with ' . $visitor->name
                        : 'Chat with anonymous visitor';

                    $lead = Lead::create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'title' => $title,
                        'person_id' => $person?->id,
                        'lead_status_id' => 1,
                        'user_owner_id' => auth()->id(),
                        'user_created_id' => auth()->id(),
                        'user_updated_id' => auth()->id(),
                    ]);

                    $record->update(['lead_id' => $lead->id]);

                    Notification::make()->title('Converted to lead')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
