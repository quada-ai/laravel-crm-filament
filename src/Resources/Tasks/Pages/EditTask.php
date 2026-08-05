<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Services\TaskService;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TaskResource::completeAction(),
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
        return TaskResource::loadCrmCustomFieldsInto($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Task $record */
        app(TaskService::class)->update(FormPayload::wrap($data), $record);
        TaskResource::saveCrmCustomFields($data, $record);

        try {
            \VentureDrake\LaravelCrm\Models\Activity::create([
                'external_id' => (string) \Illuminate\Support\Str::uuid(),
                'recordable_type' => get_class($record),
                'recordable_id' => $record->id,
                'causeable_type' => auth()->user() ? get_class(auth()->user()) : null,
                'causeable_id' => auth()->id(),
                'timelineable_type' => get_class($record),
                'timelineable_id' => $record->id,
                'description' => 'Task updated',
            ]);
        } catch (\Throwable $e) {
            // Ignore if activity creation fails
        }

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
