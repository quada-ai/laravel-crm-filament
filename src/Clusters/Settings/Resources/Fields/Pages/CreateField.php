<?php

namespace VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\FieldResource;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $models = $data['models'] ?? [];
        $options = $data['options'] ?? [];
        unset($data['models'], $data['options']);

        /** @var Field $field */
        $field = Field::create($data);

        foreach ($models as $modelClass) {
            FieldModel::create([
                'field_id' => $field->id,
                'model' => $modelClass,
            ]);
        }

        foreach ($options as $option) {
            FieldOption::create([
                'field_id' => $field->id,
                'label' => $option['label'] ?? null,
                'value' => $option['value'] ?? null,
                'order' => $option['order'] ?? 0,
            ]);
        }

        return $field;
    }
}
