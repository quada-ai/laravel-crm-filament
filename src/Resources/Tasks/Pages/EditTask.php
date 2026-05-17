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
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Task $record */
        app(TaskService::class)->update(FormPayload::wrap($data), $record);

        return $record->refresh();
    }
}

