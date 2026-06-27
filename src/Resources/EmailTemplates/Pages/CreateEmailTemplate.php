<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\EmailTemplateService;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\EmailTemplateResource;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(EmailTemplateService::class)->create($data);
    }
}
