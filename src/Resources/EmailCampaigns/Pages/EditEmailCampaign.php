<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var EmailCampaign $record */
        app(EmailCampaignService::class)->update($data, $record);

        return $record->refresh();
    }
}
