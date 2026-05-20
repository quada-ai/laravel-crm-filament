<?php

namespace VentureDrake\LaravelCrmFilament\Support;

use Illuminate\Support\Fluent;

/**
 * Wraps Filament form-data arrays as Fluent objects so they satisfy the
 * `$request->property` access shape expected by the existing CRM services
 * (e.g. LeadService::create($request)).
 *
 * Per the plugin plan ("Open questions" #1, Option A): shape Filament form
 * output to match existing service signatures.
 */
final class FormPayload
{
    public static function wrap(array $data): Fluent
    {
        return new Fluent($data);
    }
}
