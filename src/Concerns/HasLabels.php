<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
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
            ->relationship('labels', 'name')
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
                // Core CRM has no LabelObserver, so stamp external_id here.
                $label = Label::create([
                    'external_id' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'hex' => $data['hex'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $label->getKey();
            });
    }
}
