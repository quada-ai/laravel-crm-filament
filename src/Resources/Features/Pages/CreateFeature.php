<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;

class CreateFeature extends CreateRecord
{
    protected static string $resource = FeatureResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(FeatureService::class)->create($data, auth()->user());
        FeatureResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
