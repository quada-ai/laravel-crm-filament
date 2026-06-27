<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Services\SmsTemplateService;
use VentureDrake\LaravelCrmFilament\Resources\SmsTemplates\SmsTemplateResource;

class EditSmsTemplate extends EditRecord
{
    protected static string $resource = SmsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SmsTemplate $record */
        app(SmsTemplateService::class)->update($data, $record);

        return $record->refresh();
    }
}
