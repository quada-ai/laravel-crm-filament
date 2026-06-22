<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $roleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleId = isset($data['role_id']) ? (int) $data['role_id'] : null;
        unset($data['role_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->roleId === null) {
            return;
        }

        $role = Role::query()->find($this->roleId);

        if ($role) {
            $this->record->syncRoles([$role]);
        }
    }
}
