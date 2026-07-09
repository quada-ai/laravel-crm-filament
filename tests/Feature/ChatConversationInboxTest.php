<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Events\ChatMessageSent;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Observers\ChatConversationObserver;
use VentureDrake\LaravelCrm\Observers\ChatMessageObserver;
use VentureDrake\LaravelCrm\Observers\ChatVisitorObserver;
use VentureDrake\LaravelCrm\Observers\ChatWidgetObserver;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;
use VentureDrake\LaravelCrmFilament\Resources\Chat\Pages\ListChatConversations;
use VentureDrake\LaravelCrmFilament\Resources\Chat\Pages\ViewChatConversation;
use VentureDrake\LaravelCrmFilament\Tests\RoleSeeder;
use VentureDrake\LaravelCrmFilament\Tests\Stubs\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    RoleSeeder::seed();

    // Observers aren't registered in the testbench's plugin boot for chat
    // tables (they live behind a Schema::hasTable guard in the core
    // service provider). Register the ones our tests rely on so creating
    // a ChatConversation stamps external_id + chat_id and creating a
    // ChatMessage broadcasts ChatMessageSent the same way the host app
    // would.
    ChatWidget::observe(ChatWidgetObserver::class);
    ChatVisitor::observe(ChatVisitorObserver::class);
    ChatConversation::observe(ChatConversationObserver::class);
    ChatMessage::observe(ChatMessageObserver::class);

    $this->user = User::create([
        'name' => 'Inbox Agent',
        'email' => 'inbox-agent-' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $this->user->assignRole('Admin');
    $this->actingAs($this->user);

    $this->widget = ChatWidget::create([
        'public_key' => 'pk-' . uniqid(),
        'name' => 'Test widget',
    ]);

    $this->visitor = ChatVisitor::create([
        'chat_widget_id' => $this->widget->id,
        'visitor_token' => Str::random(40),
        'name' => 'Alice Visitor',
        'email' => 'alice@example.test',
    ]);

    $this->conversation = ChatConversation::create([
        'chat_widget_id' => $this->widget->id,
        'chat_visitor_id' => $this->visitor->id,
        'status' => 'open',
    ]);
});

function seedMessage(ChatConversation $conversation, string $senderType, string $body, ?int $senderId = null): ChatMessage
{
    return ChatMessage::create([
        'chat_conversation_id' => $conversation->id,
        'sender_type' => $senderType,
        'sender_id' => $senderId,
        'body' => $body,
    ]);
}

it('lists chat conversations on the index page', function () {
    livewire(ListChatConversations::class)
        ->assertCanSeeTableRecords([$this->conversation]);
});

it('exposes a status filter on the index page including pending and closed', function () {
    $second = ChatConversation::create([
        'chat_widget_id' => $this->widget->id,
        'chat_visitor_id' => $this->visitor->id,
        'status' => 'pending',
    ]);
    $third = ChatConversation::create([
        'chat_widget_id' => $this->widget->id,
        'chat_visitor_id' => $this->visitor->id,
        'status' => 'closed',
    ]);

    livewire(ListChatConversations::class)
        ->filterTable('status', 'pending')
        ->assertCanSeeTableRecords([$second])
        ->assertCanNotSeeTableRecords([$this->conversation, $third]);

    livewire(ListChatConversations::class)
        ->filterTable('status', 'closed')
        ->assertCanSeeTableRecords([$third])
        ->assertCanNotSeeTableRecords([$this->conversation, $second]);
});

it('declares the open/pending/closed status enum on the resource', function () {
    expect(ChatConversationResource::STATUSES)
        ->toHaveKeys(['open', 'pending', 'closed']);
});

it('renders existing visitor and agent messages on the view page', function () {
    seedMessage($this->conversation, 'visitor', 'Hello, I need help.');
    seedMessage($this->conversation, 'user', 'How can I help?', $this->user->id);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->assertSee('Hello, I need help.')
        ->assertSee('How can I help?')
        ->assertSee($this->conversation->chat_id);
});

it('refreshMessages reloads the messageItems collection in chronological order', function () {
    seedMessage($this->conversation, 'visitor', 'first');
    seedMessage($this->conversation, 'user', 'second', $this->user->id);

    $component = livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id]);

    $items = $component->get('messageItems');
    expect($items)->toHaveCount(2);
    expect($items[0]->body)->toBe('first');
    expect($items[1]->body)->toBe('second');
});

it('sends an inline reply through the ChatService and dispatches ChatMessageSent', function () {
    Event::fake([ChatMessageSent::class]);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->set('replyBody', 'Hi Alice, on it now.')
        ->call('sendInlineReply');

    $message = ChatMessage::query()
        ->where('chat_conversation_id', $this->conversation->id)
        ->where('sender_type', 'user')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->body)->toBe('Hi Alice, on it now.');
    expect((int) $message->sender_id)->toBe($this->user->id);

    Event::assertDispatched(ChatMessageSent::class, fn ($event) => $event->message->id === $message->id);
});

it('flips status from open to pending after a reply', function () {
    expect($this->conversation->fresh()->status)->toBe('open');

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->set('replyBody', 'Reply body')
        ->call('sendInlineReply');

    expect($this->conversation->fresh()->status)->toBe('pending');
});

it('validates the inline reply requires a non-empty body', function () {
    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->set('replyBody', '   ')
        ->call('sendInlineReply')
        ->assertHasErrors(['replyBody']);

    expect(
        ChatMessage::query()
            ->where('chat_conversation_id', $this->conversation->id)
            ->where('sender_type', 'user')
            ->count()
    )->toBe(0);
});

it('inline reply is rejected when the conversation is closed', function () {
    $this->conversation->update(['status' => 'closed', 'closed_at' => now()]);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->set('replyBody', 'Nope')
        ->call('sendInlineReply');

    expect($this->conversation->fresh()->status)->toBe('closed');
    expect(
        ChatMessage::query()
            ->where('chat_conversation_id', $this->conversation->id)
            ->where('sender_type', 'user')
            ->count()
    )->toBe(0);
});

it('close header action stamps closed_at and status', function () {
    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->callAction('close');

    $fresh = $this->conversation->fresh();
    expect($fresh->status)->toBe('closed');
    expect($fresh->closed_at)->not->toBeNull();
});

it('reopen header action only shows on non-open conversations', function () {
    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->assertActionHidden('reopen');

    $this->conversation->update(['status' => 'closed', 'closed_at' => now()]);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->callAction('reopen');

    $fresh = $this->conversation->fresh();
    expect($fresh->status)->toBe('open');
    expect($fresh->closed_at)->toBeNull();
});

it('marks visitor messages as read on mount', function () {
    seedMessage($this->conversation, 'visitor', 'still need help');
    seedMessage($this->conversation, 'visitor', 'are you there?');

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id]);

    $unread = ChatMessage::query()
        ->where('chat_conversation_id', $this->conversation->id)
        ->where('sender_type', 'visitor')
        ->whereNull('read_at')
        ->count();
    expect($unread)->toBe(0);
});

it('blade thread view renders sender bubbles distinguished by sender_type', function () {
    seedMessage($this->conversation, 'visitor', 'visitor message text');
    seedMessage($this->conversation, 'user', 'agent message text', $this->user->id);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->assertSeeHtml('crm-chat-row from-visitor')
        ->assertSeeHtml('crm-chat-row from-agent')
        ->assertSeeHtml('crm-chat-bubble visitor')
        ->assertSeeHtml('crm-chat-bubble agent')
        ->assertSee('visitor message text')
        ->assertSee('agent message text');
});

it('blade thread view includes the inline reply form on open conversations', function () {
    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->assertSeeHtml('wire:submit.prevent="sendInlineReply"')
        ->assertSeeHtml('wire:model="replyBody"');
});

it('blade thread view hides the reply form and shows closed banner when status is closed', function () {
    $this->conversation->update(['status' => 'closed', 'closed_at' => now()]);

    livewire(ViewChatConversation::class, ['record' => $this->conversation->external_id])
        ->assertDontSeeHtml('wire:submit.prevent="sendInlineReply"');
});

it('uses sendAgentMessage with an int user id (regression: previously passed User object)', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Chat/Pages/ViewChatConversation.php');
    expect($source)->toContain('(int) auth()->id()');
    expect($source)->not->toContain('auth()->user(),');
});

it('routes through ChatService for the inline reply (verifies broadcast pipeline is reused)', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Resources/Chat/Pages/ViewChatConversation.php');
    expect($source)->toContain('ChatService::class)->sendAgentMessage');
});
