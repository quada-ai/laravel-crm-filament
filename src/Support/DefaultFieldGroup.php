<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class DefaultFieldGroup
{
    public static function ensureFor(string $modelClass): FieldGroup
    {
        $table = (new FieldGroup)->getTable();
        $hasModelCol = Schema::hasColumn($table, 'model');
        $hasDefaultCol = Schema::hasColumn($table, 'default');
        $hasSystemCol = Schema::hasColumn($table, 'system');
        $hasHandleCol = Schema::hasColumn($table, 'handle');

        $query = FieldGroup::query();

        if ($hasSystemCol && $hasModelCol) {
            $group = (clone $query)->where('system', 1)->where('model', $modelClass)->first();
        } else {
            $group = null;
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

            $group = FieldGroup::create($data);
        } else {
            // Ensure existing group has system=1 or handle=default so core CRM HasCrmFields never gets null
            $updates = [];
            if ($hasSystemCol && empty($group->system)) {
                $updates['system'] = 1;
            }
            if ($hasHandleCol && empty($group->handle)) {
                $updates['handle'] = 'default';
            }
            if ($updates !== []) {
                $group->update($updates);
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
