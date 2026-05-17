<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\SmsTemplateService;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\SmsTemplateResource;

class CreateSmsTemplate extends CreateRecord
{
    protected static string $resource = SmsTemplateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SmsTemplateService::class)->create($data);
    }
}
