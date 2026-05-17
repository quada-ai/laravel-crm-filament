<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\ChatWidgetResource;

class EditChatWidget extends EditRecord
{
    protected static string $resource = ChatWidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
