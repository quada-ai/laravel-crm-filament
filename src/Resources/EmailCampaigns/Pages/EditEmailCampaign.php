<?php

namespace VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Services\EmailCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EmailCampaignResource::backToIndexAction(),
            Actions\ViewAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-eye'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var EmailCampaign $record */
        app(EmailCampaignService::class)->update(FormPayload::wrap($data)->toArray(), $record);

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
