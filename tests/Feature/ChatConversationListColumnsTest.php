<?php

use VentureDrake\LaravelCrmFilament\Concerns\UsesExternalIdRouting;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;

/**
 * Locks the US-003 8-column parity contract on ChatConversationResource.
 *
 * Structural source-grep coverage — mirrors the shape of TaskListColumnsTest,
 * PersonListColumnsTest, and OrganizationListColumnsTest from the parity
 * series. Complements ChatConversationInboxTest (which exercises the
 * Livewire mount + inline reply + action flows on ViewChatConversation).
 */
function chatConversationResourceSource(): string
{
    return file_get_contents(__DIR__ . '/../../src/Resources/Chat/ChatConversationResource.php');
}

it('references all 8 AC-named column keys in the resource source', function () {
    $source = chatConversationResourceSource();

    foreach ([
        'chat_id',
        'visitor_online',
        'visitor.name',
        'unread_count',
        'last_message_preview',
        'status',
        'last_message_at',
        'visitor_last_seen_at',
    ] as $columnKey) {
        expect($source)->toContain("TextColumn::make('{$columnKey}')");
    }
});

it('drops the prior subject and assignedToUser.name columns from the resource source', function () {
    $source = chatConversationResourceSource();

    expect($source)->not->toContain("TextColumn::make('subject')");
    expect($source)->not->toContain("TextColumn::make('assignedToUser.name')");
});

it('uses ->label(\'\') on visitor_online and unread_count for badge-only rendering', function () {
    $source = chatConversationResourceSource();

    $visitorOnlineStart = strpos($source, "TextColumn::make('visitor_online')");
    $visitorOnlineEnd = strpos($source, "TextColumn::make('visitor.name')", $visitorOnlineStart);
    $visitorOnlineBlock = substr($source, $visitorOnlineStart, $visitorOnlineEnd - $visitorOnlineStart);

    expect($visitorOnlineBlock)->toContain("->label('')");

    $unreadStart = strpos($source, "TextColumn::make('unread_count')");
    $unreadEnd = strpos($source, "TextColumn::make('last_message_preview')", $unreadStart);
    $unreadBlock = substr($source, $unreadStart, $unreadEnd - $unreadStart);

    expect($unreadBlock)->toContain("->label('')");
});

it('gates the unread_count column with a zero-hiding formatStateUsing closure', function () {
    $source = chatConversationResourceSource();

    $unreadStart = strpos($source, "TextColumn::make('unread_count')");
    $unreadEnd = strpos($source, "TextColumn::make('last_message_preview')", $unreadStart);
    $unreadBlock = substr($source, $unreadStart, $unreadEnd - $unreadStart);

    // AC: blank rendering for zero-unread rows via formatStateUsing OR visible closure
    // driven by the unread_count column body itself.
    expect($unreadBlock)->toContain('formatStateUsing');
    expect($unreadBlock)->toContain('$state > 0 ? $state : null');
});

it('uses the UsesExternalIdRouting trait via class_uses_recursive', function () {
    expect(class_uses_recursive(ChatConversationResource::class))
        ->toContain(UsesExternalIdRouting::class);
});

it('no longer declares a local getRecordRouteKeyName method on the resource', function () {
    $reflection = new ReflectionMethod(ChatConversationResource::class, 'getRecordRouteKeyName');

    // Method must be inherited from the trait, NOT declared locally on the resource.
    // Compare the method's origin file path to the trait's file path.
    $traitFile = (new ReflectionClass(UsesExternalIdRouting::class))->getFileName();

    expect($reflection->getFileName())->toBe($traitFile);
});

it('preserves defaultSort(last_message_at, desc) and the status filter block', function () {
    $source = chatConversationResourceSource();

    expect($source)->toContain("->defaultSort('last_message_at', 'desc')");
    expect($source)->toContain("SelectFilter::make('status')");
});

it('routes the 8 column labels through translation keys or literal shorthands per AC', function () {
    $source = chatConversationResourceSource();

    // The four columns the AC calls out with dedicated US-001 keys.
    expect($source)->toContain('labels.fields.last_message');
    expect($source)->toContain('labels.fields.updated');
    expect($source)->toContain('labels.fields.last_active');

    // The visitor column reuses the pre-existing chat namespace label.
    expect($source)->toContain('labels.chat.visitor');
});
