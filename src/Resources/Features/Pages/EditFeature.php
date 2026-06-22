<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Features\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Services\FeatureService;
use VentureDrake\LaravelCrmFilament\Resources\Features\FeatureResource;

class EditFeature extends EditRecord
{
    protected static string $resource = FeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return FeatureResource::loadCrmCustomFieldsInto($data, $this->record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Feature $record */
        app(FeatureService::class)->update($record, $data);
        FeatureResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
