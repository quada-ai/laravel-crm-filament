<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrmFilament\Support\ParentTypeOptions;

/**
 * Shared helpers for the standalone Note/Call/Meeting/Lunch/File/Activity
 * resources. These are read-only "global view" resources that aggregate
 * records across all parent types — the "Open parent" action below
 * derives the destination URL from the record's morph type so each list
 * can deep-link back into the owning entity's Filament resource.
 */
trait StandaloneActivityResource
{
    /**
     * Build an "Open parent" record action keyed off the supplied
     * morph_type / morph_id columns. Returns null when the parent
     * model has been deleted or its FQCN isn't mapped in
     * ParentTypeOptions, in which case the action hides itself.
     */
    protected static function openParentAction(string $typeColumn, string $idColumn): Actions\Action
    {
        return Actions\Action::make('openParent')
            ->label(__('laravel-crm-filament::labels.actions.open_parent'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (Model $record): ?string => static::buildParentUrl($record->{$typeColumn}, $record->{$idColumn}))
            ->openUrlInNewTab()
            ->visible(fn (Model $record): bool => static::buildParentUrl($record->{$typeColumn}, $record->{$idColumn}) !== null);
    }

    protected static function buildParentUrl(?string $type, $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }
        $resource = ParentTypeOptions::resourceFor($type);
        if (! $resource) {
            return null;
        }
        if (! class_exists($type)) {
            return null;
        }
        $parent = $type::query()->find($id);
        if (! $parent) {
            return null;
        }

        return $resource::getUrl('view', ['record' => $parent->external_id ?? $parent->getKey()]);
    }
}
