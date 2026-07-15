<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateEmailCampaign extends CreateRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(EmailCampaignService::class)->create(FormPayload::wrap($data)->toArray());
    }
}
