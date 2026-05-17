<?php

namespace VentureDrake\LaravelCrmFilament\Concerns;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldValue;

/**
 * Drop-in support for `venturedrake/laravel-crm` Custom Fields on a Filament Resource.
 *
 * - `crmCustomFieldsSection($modelClass)` returns a `Section` of Filament inputs
 *   keyed by `custom_fields.{field_id}`, suitable for splicing into form()->components().
 * - `loadCrmCustomFieldsInto($data, $record)` hydrates `custom_fields` from the
 *   record's polymorphic FieldValue rows.
 * - `saveCrmCustomFields($data, $record)` upserts FieldValue rows after the parent
 *   create/update has run, mirroring HasCustomFormFields::saveCustomFields().
 */
trait HasCrmCustomFields
{
    public static function crmCustomFieldsSection(string $modelClass): ?Section
    {
        $fields = FieldModel::query()
            ->where('model', $modelClass)
            ->with(['field.fieldOptions'])
            ->get()
            ->pluck('field')
            ->filter()
            ->values();

        if ($fields->isEmpty()) {
            return null;
        }

        $components = $fields
            ->map(fn (Field $field) => static::buildCrmFieldComponent($field))
            ->all();

        return Section::make('Custom fields')
            ->schema($components)
            ->collapsible()
            ->columnSpanFull();
    }

    protected static function buildCrmFieldComponent(Field $field): Component
    {
        $key = "custom_fields.{$field->id}";
        $label = $field->name;
        $required = (bool) $field->required;
        $options = $field->fieldOptions
            ->mapWithKeys(fn ($option) => [
                $option->id => $option->label ?: $option->value,
            ])
            ->all();

        return match ($field->type) {
            'textarea' => Textarea::make($key)->label($label)->required($required)->rows(3),
            'date' => DatePicker::make($key)->label($label)->required($required),
            'checkbox' => Checkbox::make($key)->label($label),
            'select' => Select::make($key)->label($label)->options($options)->searchable()->required($required),
            'select_multiple' => Select::make($key)->label($label)->multiple()->options($options)->searchable()->required($required),
            'radio' => Radio::make($key)->label($label)->options($options)->required($required),
            'checkbox_multiple' => CheckboxList::make($key)->label($label)->options($options)->required($required),
            default => TextInput::make($key)->label($label)->required($required)->maxLength(255),
        };
    }

    public static function loadCrmCustomFieldsInto(array $data, $record): array
    {
        if (! $record || ! method_exists($record, 'fields')) {
            return $data;
        }

        $data['custom_fields'] = [];

        foreach ($record->fields()->with('field')->get() as $fv) {
            $value = $fv->value;
            $type = $fv->field?->type;
            if (in_array($type, ['checkbox_multiple', 'select_multiple'], true) && $value !== null) {
                $value = json_decode($value, true) ?: [];
            } elseif ($type === 'checkbox') {
                $value = (bool) $value;
            }
            $data['custom_fields'][$fv->field_id] = $value;
        }

        return $data;
    }

    public static function saveCrmCustomFields(array $data, $record): void
    {
        $values = $data['custom_fields'] ?? null;
        if (! is_array($values) || ! $record || ! method_exists($record, 'fields')) {
            return;
        }

        foreach ($values as $fieldId => $value) {
            if (is_array($value)) {
                $value = json_encode(array_values(array_filter($value, fn ($v) => $v !== null && $v !== '')));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            FieldValue::updateOrCreate(
                [
                    'field_id' => $fieldId,
                    'field_valueable_type' => get_class($record),
                    'field_valueable_id' => $record->id,
                ],
                ['value' => $value],
            );
        }
    }
}
