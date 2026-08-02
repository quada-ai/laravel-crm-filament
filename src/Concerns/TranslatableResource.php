<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

trait TranslatableResource
{
    public static function getNavigationGroup(): ?string
    {
        $groupKey = static::getNavigationGroupKey();

        return LaravelCrmPlugin::get()->getNavigationGroup()
            ?? __('laravel-crm-filament::labels.navigation.groups.' . $groupKey);
    }

    public static function getModelLabel(): string
    {
        return __('laravel-crm-filament::labels.resources.' . static::getResourceTranslationKey() . '.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('laravel-crm-filament::labels.resources.' . static::getResourceTranslationKey() . '.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('laravel-crm-filament::labels.resources.' . static::getResourceTranslationKey() . '.navigation');
    }

    protected static function getResourceTranslationKey(): string
    {
        if (isset(static::$resourceTranslationKey)) {
            return static::$resourceTranslationKey;
        }

        $baseName = class_basename(static::class);
        $baseName = Str::before($baseName, 'Resource');

        return Str::snake($baseName);
    }

    protected static function getNavigationGroupKey(): string
    {
        if (isset(static::$navigationGroupKey)) {
            return static::$navigationGroupKey;
        }

        return 'settings';
    }
}
