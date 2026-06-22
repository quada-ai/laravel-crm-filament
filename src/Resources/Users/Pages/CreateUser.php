<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrmFilament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $roleId = null;

    /** @var array<int, int> */
    protected array $crmTeamIds = [];

    /** @var array<int, array<string, mixed>> */
    protected array $phonesPayload = [];

    /** @var array<int, array<string, mixed>> */
    protected array $addressesPayload = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->roleId = isset($data['role_id']) ? (int) $data['role_id'] : null;
        $this->crmTeamIds = collect($data['crm_team_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->phonesPayload = $data['phones'] ?? [];
        $this->addressesPayload = $data['addresses'] ?? [];

        unset($data['role_id'], $data['crm_team_ids'], $data['phones'], $data['addresses']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($this->roleId !== null) {
            $role = Role::query()->find($this->roleId);
            if ($role) {
                $record->syncRoles([$role]);
            }
        }

        if (method_exists($record, 'crmTeams')) {
            $record->crmTeams()->sync($this->crmTeamIds);
        }

        UserResource::syncMorphRows($record, 'phones', $this->phonesPayload, Phone::class, ['number', 'type']);
        UserResource::syncMorphRows($record, 'addresses', $this->addressesPayload, Address::class, ['line1', 'line2', 'line3', 'city', 'state', 'code', 'country']);
    }
}
