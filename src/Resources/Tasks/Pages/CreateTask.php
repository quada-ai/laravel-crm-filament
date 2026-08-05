<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Tasks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Services\TaskService;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = app(TaskService::class)->create(FormPayload::wrap($data));
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
                'description' => 'Task created',
            ]);
        } catch (\Throwable $e) {
            // Ignore if activity creation fails
        }

        return $record;
    }
}
