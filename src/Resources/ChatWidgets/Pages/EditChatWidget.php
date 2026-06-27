<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;

class EditChatWidget extends EditRecord
{
    protected static string $resource = ChatWidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
