<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Services\EmailTemplateService;
use VentureDrake\LaravelCrmFilament\Resources\EmailTemplates\EmailTemplateResource;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var EmailTemplate $record */
        app(EmailTemplateService::class)->update($data, $record);

        return $record->refresh();
    }
}
