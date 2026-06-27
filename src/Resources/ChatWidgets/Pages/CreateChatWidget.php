<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;

class CreateChatWidget extends CreateRecord
{
    protected static string $resource = ChatWidgetResource::class;
}
