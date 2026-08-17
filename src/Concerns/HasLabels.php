<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Label;

/**
 * Drop-in inline-tagging support for `venturedrake/laravel-crm` Labels on a
 * Filament Resource.
 *
 * `labelsField()` returns a `Select::make('labels')->multiple()->relationship('labels','name')->preload()`
 * with a `createOptionForm(...)` that lets users mint new labels inline.
 * Persists through the model's `labels()` morphToMany relation, which writes
 * to the core `crm_labelables` polymorphic pivot.
 */
trait HasLabels
{
    public static function labelsField(): Select
    {
        return Select::make('labels')
            ->label(__('laravel-crm-filament::labels.fields.labels'))
            ->multiple()
            ->relationship(
                'labels',
                'name',
                modifyQueryUsing: fn (Builder $query) => static::scopeLabelsQueryToTenant($query)
            )
            ->dehydrated(true)
            ->preload()
            ->searchable()
            ->createOptionForm([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    ColorPicker::make('hex')
                        ->label(__('laravel-crm-filament::labels.fields.color')),
                ]),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->createOptionUsing(function (array $data): int {
                $payload = [
                    'external_id' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'hex' => $data['hex'] ?? null,
                    'description' => $data['description'] ?? null,
                ];

                if ($tenant = Filament::getTenant()) {
                    $table = (new Label)->getTable();
                    if (Schema::hasColumn($table, 'tenant_id')) {
                        $payload['tenant_id'] = $tenant->getKey();
                    } elseif (Schema::hasColumn($table, 'team_id')) {
                        $payload['team_id'] = $tenant->getKey();
                    }
                }

                $label = Label::create($payload);

                return $label->getKey();
            });
    }

    protected static function scopeLabelsQueryToTenant(Builder $query): Builder
    {
        if ($tenant = Filament::getTenant()) {
            $table = $query->getModel()->getTable();
            if (Schema::hasColumn($table, 'tenant_id')) {
                $query->where($table . '.tenant_id', $tenant->getKey());
            } elseif (Schema::hasColumn($table, 'team_id')) {
                $query->where($table . '.team_id', $tenant->getKey());
            }
        }

        return $query;
    }
}
