<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Monitors\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Fall back to the same defaults used by the Create form so existing
        // monitors that pre-date these columns still show populated inputs
        // rather than blank fields.
        $data['downtime_minutes_before_alert'] ??= 5;
        $data['perf_threshold_ms'] ??= 3500;
        $data['expected_status_code'] ??= 200;
        $data['interval'] ??= 5;
        $data['user_owner_id'] ??= auth()->id();

        return $data;
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
