<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Chat\Pages;

use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;

class ListChatConversations extends ListRecords
{
    protected static string $resource = ChatConversationResource::class;
}
