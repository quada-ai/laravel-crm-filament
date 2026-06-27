<?php

namespace VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrmFilament\Resources\ChatWidgets\ChatWidgetResource;

class ViewChatWidget extends ViewRecord
{
    protected static string $resource = ChatWidgetResource::class;

    protected string $view = 'laravel-crm-filament::chat-widgets.embed';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }

    public function getEmbedSnippet(): string
    {
        /** @var ChatWidget $widget */
        $widget = $this->record;

        $url = route('laravel-crm.portal.chat.embed', ['publicKey' => $widget->public_key]);

        return '<script src="' . $url . '" async defer></script>';
    }

    public function getIframeUrl(): string
    {
        /** @var ChatWidget $widget */
        $widget = $this->record;

        return route('laravel-crm.portal.chat.widget', ['publicKey' => $widget->public_key]);
    }
}
