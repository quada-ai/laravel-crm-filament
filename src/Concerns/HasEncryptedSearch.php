<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds an encrypted-field aware modifyQueryUsing callback to a Filament table.
 *
 * When LARAVEL_CRM_ENCRYPT_DB_FIELDS is true, the named columns contain cipher
 * text — SQL LIKE searches are meaningless. This concern:
 *  1. Marks those columns as NOT searchable (suppresses Filament's default SQL search).
 *  2. Provides a table->modifyQueryUsing() callback that performs a PHP-side
 *     decrypt-and-compare when a global search term is active.
 *
 * Usage (in a Resource table() method):
 *   ->modifyQueryUsing(HasEncryptedSearch::modifyQuery(
 *       fn ($record) => $record->first_name.' '.$record->last_name
 *   ))
 */
class HasEncryptedSearch
{
    /**
     * Return a modifyQueryUsing closure that handles encrypted-field search.
     *
     * @param  Closure(mixed): string  $accessor  Returns the plain-text string to search against.
     * @param  list<string>  $sqlSearchColumns  Columns that CAN be searched via SQL (non-encrypted).
     */
    public static function modifyQuery(
        Closure $accessor,
        array $sqlSearchColumns = []
    ): Closure {
        return function (Builder $query) use ($accessor) {
            if (! config('laravel-crm.encrypt_db_fields', false)) {
                return $query;
            }

            $search = request('tableSearch') ?? request('search') ?? '';

            if (blank($search)) {
                return $query;
            }

            // Collect matching IDs in PHP (decrypt then match).
            $term = mb_strtolower($search);
            $matchingIds = $query->getModel()::withoutGlobalScopes()
                ->select('id')
                ->get()
                ->filter(fn ($record) => str_contains(mb_strtolower($accessor($record)), $term))
                ->pluck('id');

            return $query->whereIn($query->getModel()->getKeyName(), $matchingIds);
        };
    }
}
