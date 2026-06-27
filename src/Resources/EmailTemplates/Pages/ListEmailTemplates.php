<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\EmailTemplateResource;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
