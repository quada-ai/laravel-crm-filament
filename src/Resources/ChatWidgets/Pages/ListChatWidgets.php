<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;

class ListChatWidgets extends ListRecords
{
    protected static string $resource = ChatWidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
