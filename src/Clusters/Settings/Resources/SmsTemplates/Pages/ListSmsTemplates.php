<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\SmsTemplateResource;

class ListSmsTemplates extends ListRecords
{
    protected static string $resource = SmsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
