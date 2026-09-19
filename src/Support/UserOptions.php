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

        $table = (new $userModelClass)->getTable();
        $tenant = Filament::getTenant();

        if ($tenant) {
            if (method_exists($tenant, 'users')) {
                $query = $tenant->users();

                if (Schema::hasColumn($table, 'role')) {
                    $query->where(function ($q) use ($table) {
                        $q->where("{$table}.role", '!=', 'admin')
                            ->orWhereNull("{$table}.role");
                    });
                }

                return $query->orderBy("{$table}.name")->pluck("{$table}.name", "{$table}.id")->toArray();
            }

            $tenantId = $tenant->getKey();
            $query = $userModelClass::query();

            if (Schema::hasColumn($table, 'role')) {
                $query->where(function ($q) use ($table) {
                    $q->where("{$table}.role", '!=', 'admin')
                        ->orWhereNull("{$table}.role");
                });
            }

            if (Schema::hasColumn($table, 'current_crm_team_id')) {
                return $query->where('current_crm_team_id', $tenantId)
                    ->orderBy("{$table}.name")
                    ->pluck("{$table}.name", "{$table}.id")
                    ->toArray();
            }

            if (Schema::hasColumn($table, 'team_id')) {
                return $query->where('team_id', $tenantId)
                    ->orderBy("{$table}.name")
                    ->pluck("{$table}.name", "{$table}.id")
                    ->toArray();
            }
        }

        $query = $userModelClass::query();

        if (Schema::hasColumn($table, 'role')) {
            $query->where(function ($q) use ($table) {
                $q->where("{$table}.role", '!=', 'admin')
                    ->orWhereNull("{$table}.role");
            });
        }

        return $query->orderBy("{$table}.name")->pluck("{$table}.name", "{$table}.id")->toArray();
    }
}
