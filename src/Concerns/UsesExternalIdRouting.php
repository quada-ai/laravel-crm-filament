<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Routes the resource by the model's `external_id` column instead of the
 * integer primary key, and patches Filament's URL helper so links built
 * with `Resource::getUrl('view', ['record' => $model])` substitute the
 * external_id rather than the PK.
 *
 * Filament v5 only consults getRecordRouteKeyName() during binding
 * RESOLUTION. Without the getUrl() override Laravel's route() helper
 * substitutes $model->getRouteKey() (the integer PK) and the resolver
 * then 404s when it looks up `external_id = '<pk>'`.
 */
trait UsesExternalIdRouting
{
    public static function getRecordRouteKeyName(): ?string
    {
        return 'external_id';
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        if (($key = static::getRecordRouteKeyName()) && isset($parameters['record']) && $parameters['record'] instanceof Model) {
            $parameters['record'] = $parameters['record']->getAttribute($key);
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            $model = $query->getModel();
            if (\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'tenant_id')) {
                $query->where($model->getTable() . '.tenant_id', $tenant->getKey());
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'team_id')) {
                $query->where($model->getTable() . '.team_id', $tenant->getKey());
            }
        }

        return $query;
    }
}
