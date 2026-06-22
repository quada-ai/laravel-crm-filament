<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Users\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $roleId = null;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role_id'] = optional($this->getRecord()->roles()->first())->id;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->roleId = isset($data['role_id']) ? (int) $data['role_id'] : null;
        unset($data['role_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->roleId !== null ? Role::query()->find($this->roleId) : null;

        if ($role) {
            $this->record->syncRoles([$role]);
        } else {
            $this->record->syncRoles([]);
        }
    }
}
