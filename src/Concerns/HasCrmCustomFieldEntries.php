<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * Shared infolist helper for rendering core CRM Custom FieldValues on any Resource.
 *
 * - `crmCustomFieldEntries($record, $grouped = false)` returns either a flat list
 *   of TextEntry components (when `$grouped` is false) or one Section per distinct
 *   FieldGroup (when `$grouped` is true).
 * - `buildCrmCustomFieldTextEntry($fieldValue)` builds a single TextEntry whose
 *   `state()` closure mirrors the per-type display formatting used by core's
 *   `resources/views/components/custom-field-values/_row.blade.php` partial:
 *     - checkbox: Yes/No
 *     - select / radio: looked-up option label
 *     - checkbox_multiple: JSON-decoded list joined with ", "
 *     - anything else: raw value
 *
 * The `$record` parameter accepts any model that exposes a polymorphic `fields()`
 * morphMany relation (e.g. Lead, Deal, Quote, Order, Invoice — the entities that
 * use core's HasCrmFields trait).
 */
trait HasCrmCustomFieldEntries
{
    /**
     * @return array<int, TextEntry|Section>
     */
    public static function crmCustomFieldEntries($record, bool $grouped = false): array
    {
        $fieldValues = $record->fields()
            ->with(['field.fieldGroup', 'field.fieldOptions'])
            ->get()
            ->filter(fn ($fv) => $fv->field !== null);

        if (! $grouped) {
            return $fieldValues
                ->filter(fn ($fv) => $fv->field->field_group_id === null)
                ->values()
                ->map(fn ($fv) => static::buildCrmCustomFieldTextEntry($fv))
                ->all();
        }

        $groupedValues = $fieldValues->filter(fn ($fv) => $fv->field->field_group_id !== null);

        return $groupedValues
            ->groupBy(fn ($fv) => $fv->field->field_group_id)
            ->map(function ($groupFieldValues) {
                $group = $groupFieldValues->first()->field->fieldGroup;
                $entries = $groupFieldValues
                    ->map(fn ($fv) => static::buildCrmCustomFieldTextEntry($fv))
                    ->all();

                return Section::make($group->name)->schema($entries);
            })
            ->values()
            ->all();
    }

    public static function buildCrmCustomFieldTextEntry($fieldValue): TextEntry
    {
        $field = $fieldValue->field;
        $fieldName = $field->name;
        $raw = $fieldValue->value;
        $type = $field->type;

        return TextEntry::make("custom.{$fieldName}")
            ->label(ucfirst($fieldName))
            ->state(function () use ($field, $raw, $type) {
                switch ($type) {
                    case 'checkbox':
                        return ((bool) $raw)
                            ? ucfirst(__('laravel-crm::lang.yes'))
                            : ucfirst(__('laravel-crm::lang.no'));

                    case 'select':
                    case 'radio':
                        $option = $field->fieldOptions->firstWhere('id', $raw);

                        return $option?->label;

                    case 'checkbox_multiple':
                        $values = is_string($raw) ? json_decode($raw, true) : $raw;
                        $values = is_array($values) ? $values : [];

                        return $field->fieldOptions
                            ->whereIn('id', $values)
                            ->pluck('label')
                            ->implode(', ');

                    default:
                        return $raw;
                }
            });
    }
}
