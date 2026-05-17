<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(DealService::class)->create(FormPayload::wrap($data));
        DealResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
