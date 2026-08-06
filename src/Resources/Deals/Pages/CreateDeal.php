<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Deals\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\DealService;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $pipeline = \VentureDrake\LaravelCrmFilament\Support\DefaultPipeline::ensureFor(\VentureDrake\LaravelCrm\Models\Deal::class);
        \VentureDrake\LaravelCrmFilament\Support\DefaultFieldGroup::ensureFor(\VentureDrake\LaravelCrm\Models\Deal::class);
        if (empty($data['pipeline_id'])) {
            $data['pipeline_id'] = $pipeline->id;
        }
        if (empty($data['pipeline_stage_id'])) {
            $stage = $pipeline->pipelineStages()->orderBy('order')->first();
            $data['pipeline_stage_id'] = $stage?->id;
        }

        $person = isset($data['person_id']) ? Person::find($data['person_id']) : null;
        $organization = isset($data['organization_id']) ? Organization::find($data['organization_id']) : null;

        $record = app(DealService::class)->create(FormPayload::wrap($data), $person, $organization);
        if (array_key_exists('labels', $data)) {
            $record->labels()->sync($data['labels'] ?? []);
        }
        DealResource::saveCrmCustomFields($data, $record);

        return $record;
    }
}
