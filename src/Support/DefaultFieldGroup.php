<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class DefaultFieldGroup
{
    public static function resolveCurrentTeamId(): ?int
    {
        if (class_exists(\Filament\Facades\Filament::class)) {
            try {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant) {
                    return (int) $tenant->getKey();
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $user = Auth::user();
        if ($user) {
            if (isset($user->current_crm_team_id)) {
                return (int) $user->current_crm_team_id;
            }
            if (isset($user->current_team_id)) {
                return (int) $user->current_team_id;
            }
            if (isset($user->team_id)) {
                return (int) $user->team_id;
            }
        }

        return null;
    }

    public static function ensureFor(string $modelClass): FieldGroup
    {
        $table = (new FieldGroup)->getTable();
        $hasModelCol = Schema::hasColumn($table, 'model');
        $hasDefaultCol = Schema::hasColumn($table, 'default');
        $hasSystemCol = Schema::hasColumn($table, 'system');
        $hasHandleCol = Schema::hasColumn($table, 'handle');
        $hasTeamCol = Schema::hasColumn($table, 'team_id');

        $teamId = static::resolveCurrentTeamId();

        $query = FieldGroup::withoutGlobalScopes();

        // 1. Try team-specific + system/handle/model matching
        $group = null;
        if ($teamId && $hasTeamCol) {
            $group = (clone $query)->where('team_id', $teamId)->first();
        }

        if (! $group && $hasSystemCol) {
            $group = (clone $query)->where('system', 1)->first();
        }

        if (! $group && $hasHandleCol) {
            $group = (clone $query)->where('handle', 'default')->first();
        }

        if (! $group && $hasModelCol && $hasDefaultCol) {
            $group = (clone $query)->where('model', $modelClass)->where('default', 1)->first()
                ?? (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->where('default', 1)->first()
                ?? (clone $query)->whereNull('model')->first();
        } elseif (! $group && $hasModelCol) {
            $group = (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->first();
        } elseif (! $group && $hasDefaultCol) {
            $group = (clone $query)->where('default', 1)->first();
        }

        if (! $group) {
            $group = (clone $query)->first();
        }

        if (! $group) {
            $data = [
                'external_id' => (string) Str::uuid(),
                'name' => 'Default',
            ];
            if ($hasModelCol) {
                $data['model'] = $modelClass;
            }
            if ($hasDefaultCol) {
                $data['default'] = 1;
            }
            if ($hasSystemCol) {
                $data['system'] = 1;
            }
            if ($hasHandleCol) {
                $data['handle'] = 'default';
            }
            if ($hasTeamCol && $teamId) {
                $data['team_id'] = $teamId;
            }

            $group = FieldGroup::create($data);
        } else {
            // Ensure existing group has system=1 / handle=default / team_id
            $updates = [];
            if ($hasSystemCol && empty($group->system)) {
                $updates['system'] = 1;
            }
            if ($hasHandleCol && empty($group->handle)) {
                $updates['handle'] = 'default';
            }
            if ($hasTeamCol && $teamId && empty($group->team_id)) {
                $updates['team_id'] = $teamId;
            }
            if ($updates !== []) {
                $group->update($updates);
            }
        }

        // Also ensure a team-scoped group exists if current tenant has a teamId
        if ($teamId && $hasTeamCol) {
            $teamGroup = FieldGroup::withoutGlobalScopes()->where('team_id', $teamId)->first();
            if (! $teamGroup) {
                $data = [
                    'external_id' => (string) Str::uuid(),
                    'name' => 'Default',
                    'team_id' => $teamId,
                ];
                if ($hasModelCol) {
                    $data['model'] = $modelClass;
                }
                if ($hasDefaultCol) {
                    $data['default'] = 1;
                }
                if ($hasSystemCol) {
                    $data['system'] = 1;
                }
                if ($hasHandleCol) {
                    $data['handle'] = 'default';
                }
                FieldGroup::create($data);
            }
        }

        return $group;
    }

    public static function ensureAll(): void
    {
        $models = [
            \VentureDrake\LaravelCrm\Models\Deal::class,
            \VentureDrake\LaravelCrm\Models\Lead::class,
            \VentureDrake\LaravelCrm\Models\Person::class,
            \VentureDrake\LaravelCrm\Models\Organization::class,
            \VentureDrake\LaravelCrm\Models\Quote::class,
            \VentureDrake\LaravelCrm\Models\Order::class,
            \VentureDrake\LaravelCrm\Models\Invoice::class,
        ];

        foreach ($models as $model) {
            try {
                static::ensureFor($model);
            } catch (\Throwable $e) {
                // Ignore if DB not migrated yet
            }
        }
    }
}
