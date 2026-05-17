<?php

namespace VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Services\SmsCampaignService;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;

class EditSmsCampaign extends EditRecord
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SmsCampaign $record */
        app(SmsCampaignService::class)->update($data, $record);

        return $record->refresh();
    }
}
