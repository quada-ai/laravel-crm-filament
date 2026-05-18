<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Closure;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;

/**
 * Augments a Filament Resource's global search to handle encrypted name fields.
 *
 * When `laravel-crm.encrypt_db_fields` is enabled the searchable columns contain
 * cipher text; standard LIKE search never matches. This trait overrides
 * getGlobalSearchResults() to do a PHP-side decrypt + substring compare instead.
 *
 * Hosts must still expose `getGloballySearchableAttributes()` (for the un-encrypted
 * case) and `getGlobalSearchResultTitle()` / `Details()`. Resources that want this
 * behavior implement `crmEncryptedSearchAccessor()` returning the plaintext
 * string to compare against per record.
 */
trait HasEncryptedGlobalSearch
{
    public static function getGlobalSearchResults(string $search): Collection
    {
        if (! config('laravel-crm.encrypt_db_fields', false)) {
            return parent::getGlobalSearchResults($search);
        }

        $term = trim($search);
        if ($term === '') {
            return collect();
        }

        $needle = mb_strtolower($term);
        $accessor = static::crmEncryptedSearchAccessor();

        return static::getGlobalSearchEloquentQuery()
            ->limit(static::getGlobalSearchResultsLimit() * 4)
            ->get()
            ->filter(fn ($record) => str_contains(mb_strtolower((string) $accessor($record)), $needle))
            ->take(static::getGlobalSearchResultsLimit())
            ->map(fn ($record) => new GlobalSearchResult(
                title: static::getGlobalSearchResultTitle($record),
                url: static::getGlobalSearchResultUrl($record),
                details: static::getGlobalSearchResultDetails($record),
                actions: static::getGlobalSearchResultActions($record),
            ))
            ->values();
    }

    /**
     * Returns a closure that, given a record, returns the plaintext haystack
     * to substring-match against.
     */
    abstract protected static function crmEncryptedSearchAccessor(): Closure;
}
