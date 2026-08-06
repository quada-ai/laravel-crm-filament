<?php

namespace VentureDrake\LaravelCrmFilament\Support;

class UserOptions
{
    public static function get(): array
    {
        $userModelClass = config('auth.providers.users.model', \App\Models\User::class);
        if (is_string($userModelClass) && class_exists($userModelClass)) {
            return $userModelClass::query()->pluck('name', 'id')->toArray();
        }

        return [];
    }
}
