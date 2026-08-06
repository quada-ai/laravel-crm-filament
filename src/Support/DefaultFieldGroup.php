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

        $query = FieldGroup::query();

        if ($hasModelCol && $hasDefaultCol) {
            $group = (clone $query)->where('model', $modelClass)->where('default', 1)->first()
                ?? (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->where('default', 1)->first()
                ?? (clone $query)->whereNull('model')->first()
                ?? (clone $query)->first();
        } elseif ($hasModelCol) {
            $group = (clone $query)->where('model', $modelClass)->first()
                ?? (clone $query)->whereNull('model')->first()
                ?? (clone $query)->first();
        } elseif ($hasDefaultCol) {
            $group = (clone $query)->where('default', 1)->first()
                ?? (clone $query)->first();
        } else {
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

            $group = FieldGroup::create($data);
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
