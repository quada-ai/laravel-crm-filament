<?php

namespace VentureDrake\LaravelCrmFilament\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Thin Eloquent binding for the `audits` table written by
 * `owen-it/laravel-auditing` Auditable models. We read these rows from
 * the table directly so the AuditsRelationManager doesn't depend on the
 * auditing package being a composer require of the plugin — the host
 * app is the source of truth for whether auditing is installed and
 * actively writing rows.
 */
class Audit extends Model
{
    protected $table = 'audits';

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
