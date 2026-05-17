<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\ChatWidgetResource;

class CreateChatWidget extends CreateRecord
{
    protected static string $resource = ChatWidgetResource::class;
}
