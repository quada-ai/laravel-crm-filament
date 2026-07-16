<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateSmsCampaign extends CreateRecord
{
    protected static string $resource = SmsCampaignResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SmsCampaignService::class)->create(FormPayload::wrap($data)->toArray());
    }
}
