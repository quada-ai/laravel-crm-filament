<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Monitor;
use VentureDrake\LaravelCrm\Services\MonitorService;
use VentureDrake\LaravelCrmFilament\Resources\Monitors\MonitorResource;

class EditMonitor extends EditRecord
{
    protected static string $resource = MonitorResource::class;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Monitor $record */
        app(MonitorService::class)->update($record, $data);

        return $record->refresh();
    }
}
