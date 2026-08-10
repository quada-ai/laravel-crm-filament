<?php

namespace VentureDrake\LaravelCrmFilament\Support;

class UserOptions
{
    public static function get(): array
    {
        $userModelClass = config('auth.providers.users.model', \App\Models\User::class);
        if (is_string($userModelClass) && class_exists($userModelClass)) {
            $tenant = \Filament\Facades\Filament::getTenant();

            if ($tenant) {
                if (method_exists($tenant, 'users')) {
                    return $tenant->users()->pluck('name', 'users.id')->toArray();
                }

                $tenantId = $tenant->getKey();

                if (\Illuminate\Support\Facades\Schema::hasColumn((new $userModelClass)->getTable(), 'current_crm_team_id')) {
                    return $userModelClass::query()
                        ->where('current_crm_team_id', $tenantId)
                        ->pluck('name', 'id')
                        ->toArray();
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn((new $userModelClass)->getTable(), 'team_id')) {
                    return $userModelClass::query()
                        ->where('team_id', $tenantId)
                        ->pluck('name', 'id')
                        ->toArray();
                }
            }

            return $userModelClass::query()->pluck('name', 'id')->toArray();
        }

        return [];
    }
}
