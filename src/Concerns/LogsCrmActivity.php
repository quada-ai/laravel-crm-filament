<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Mirror legacy NoteRelated/TaskRelated/CallRelated/MeetingRelated activity logging:
 * each create against a parent record inserts an Activity row that powers the
 * core CRM's timeline / audit feed.
 *
 * Intended for use inside Filament RelationManager CreateAction->after() callbacks.
 */
trait LogsCrmActivity
{
    public static function logCrmActivity(Model $parent, Model $child): void
    {
        if (! method_exists($parent, 'activities')) {
            return;
        }

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $parent->activities()->create([
            'causeable_type' => $user->getMorphClass(),
            'causeable_id' => $user->id,
            'timelineable_type' => $parent->getMorphClass(),
            'timelineable_id' => $parent->id,
            'recordable_type' => $child->getMorphClass(),
            'recordable_id' => $child->id,
        ]);
    }
}
