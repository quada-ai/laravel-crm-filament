<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;

class UserOptions
{
    public static function get(): array
    {
        $userModelClass = config('auth.providers.users.model', \App\Models\User::class);

        if (! is_string($userModelClass) || ! class_exists($userModelClass)) {
            return [];
        }

        $tenant = Filament::getTenant();

        if ($tenant) {
            if (method_exists($tenant, 'users')) {
                return $tenant->users()->orderBy('name')->pluck('name', 'users.id')->toArray();
            }

            $tenantId = $tenant->getKey();

            if (Schema::hasColumn((new $userModelClass)->getTable(), 'current_crm_team_id')) {
                return $userModelClass::query()
                    ->where('current_crm_team_id', $tenantId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }

            if (Schema::hasColumn((new $userModelClass)->getTable(), 'team_id')) {
                return $userModelClass::query()
                    ->where('team_id', $tenantId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            }
        }

        return $userModelClass::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }
}
