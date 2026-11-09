<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Fields\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
use VentureDrake\LaravelCrmFilament\Resources\Fields\FieldResource;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenantId = class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant()?->id : null;
        if ($tenantId) {
            $data['tenant_id'] = $tenantId;
            $data['team_id'] = $tenantId;
        }

        $models = $data['models'] ?? [];
        $options = $data['options'] ?? [];
        unset($data['models'], $data['options']);

        /** @var Field $field */
        $field = Field::create($data);

        foreach ($models as $modelClass) {
            $modelData = [
                'field_id' => $field->id,
                'model' => $modelClass,
            ];
            if ($tenantId) {
                $modelData['tenant_id'] = $tenantId;
                $modelData['team_id'] = $tenantId;
            }
            FieldModel::create($modelData);
        }

        foreach ($options as $option) {
            $optData = [
                'field_id' => $field->id,
                'label' => $option['label'] ?? null,
                'value' => $option['value'] ?? null,
                'order' => $option['order'] ?? 0,
            ];
            if ($tenantId) {
                $optData['tenant_id'] = $tenantId;
                $optData['team_id'] = $tenantId;
            }
            FieldOption::create($optData);
        }

        return $field;
    }
}
