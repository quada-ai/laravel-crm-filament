<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;

class ListSmsCampaigns extends ListRecords
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
